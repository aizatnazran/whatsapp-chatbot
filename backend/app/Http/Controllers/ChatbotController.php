<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\ChatSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use GuzzleHttp\Client;

class ChatbotController extends Controller
{
    public function webhook(Request $request)
    {
        Log::info('Raw webhook request', [
            'method' => $request->method(),
            'all_data' => $request->all(),
            'headers' => $request->headers->all(),
            'content' => $request->getContent()
        ]);

        // Verify WhatsApp webhook
        if ($request->isMethod('get')) {
            $token = env('VERIFY_TOKEN');
            $mode = $request->input('hub_mode');
            $challenge = $request->input('hub_challenge');
            $verify_token = $request->input('hub_verify_token');

            Log::info('Webhook verification attempt', [
                'mode' => $mode,
                'verify_token' => $verify_token,
                'challenge' => $challenge,
                'expected_token' => $token
            ]);

            if ($verify_token === $token) {
                Log::info('Webhook verification successful');
                return response((string)$challenge, 200)->header('Content-Type', 'text/plain');
            }

            Log::error('Webhook verification failed', [
                'mode' => $mode,
                'verify_token' => $verify_token,
                'token' => $token
            ]);
            return response('Forbidden', 403);
        }

        // Handle incoming messages
        $payload = $request->all();
        Log::info('Webhook payload received', ['payload' => $payload]);
        
        try {
            if (!empty($payload['entry'][0]['changes'][0]['value']['messages'])) {
                $message = $payload['entry'][0]['changes'][0]['value']['messages'][0];
                Log::info('Message received', ['message' => $message]);
                
                $phone = $message['from'];
                $text = $message['text']['body'] ?? '';

                return $this->handleMessage($phone, $text);
            } else {
                Log::info('No message in payload', ['entry' => $payload['entry'] ?? null]);
            }
        } catch (\Exception $e) {
            Log::error('Error processing message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return response()->json(['status' => 'success']);
    }

    private function handleMessage($phone, $text)
    {
        Log::info('Handling message', [
            'phone' => $phone,
            'text' => $text
        ]);

        try {
            $session = ChatSession::firstOrCreate(
                ['phone_number' => $phone],
                ['current_step' => 'start', 'temp_data' => []]
            );

            Log::info('Chat session', [
                'session' => $session,
                'step' => $session->current_step
            ]);

            $response = '';

            switch ($session->current_step) {
                case 'start':
                    $response = "Welcome to our appointment booking system! Please enter your full name:";
                    $session->update(['current_step' => 'name']);
                    break;

                case 'name':
                    $session->update([
                        'temp_data' => array_merge($session->temp_data ?? [], ['name' => $text]),
                        'current_step' => 'email'
                    ]);
                    $response = "Great! Now please enter your email address:";
                    break;

                case 'email':
                    $validator = Validator::make(['email' => $text], [
                        'email' => 'required|email'
                    ]);

                    if ($validator->fails()) {
                        $response = "Invalid email format. Please enter a valid email address:";
                        break;
                    }

                    $session->update([
                        'temp_data' => array_merge($session->temp_data ?? [], ['email' => $text]),
                        'current_step' => 'phone'
                    ]);
                    $response = "Perfect! Now please confirm your phone number:";
                    break;

                case 'phone':
                    $validator = Validator::make(['phone' => $text], [
                        'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10'
                    ]);

                    if ($validator->fails()) {
                        $response = "Invalid phone number format. Please enter a valid phone number:";
                        break;
                    }

                    // Create user
                    $user = User::create([
                        'name' => $session->temp_data['name'],
                        'email' => $session->temp_data['email'],
                        'phone_number' => $text,
                        'password' => bcrypt(Str::random(16))
                    ]);

                    $session->update([
                        'current_step' => 'appointment_date',
                        'temp_data' => ['user_id' => $user->id]
                    ]);
                    $response = "Great! Now please select your preferred appointment date (YYYY-MM-DD):";
                    break;

                case 'appointment_date':
                    $validator = Validator::make(['date' => $text], [
                        'date' => 'required|date|after:today'
                    ]);

                    if ($validator->fails()) {
                        $response = "Invalid date format or date is in the past. Please enter a future date (YYYY-MM-DD):";
                        break;
                    }

                    $session->update([
                        'temp_data' => array_merge($session->temp_data ?? [], ['date' => $text]),
                        'current_step' => 'appointment_time'
                    ]);
                    $response = "Available time slots:\n9:00 AM\n11:00 AM\n2:00 PM\n4:00 PM\nPlease select your preferred time:";
                    break;

                case 'appointment_time':
                    $validTimes = ['9:00 AM', '11:00 AM', '2:00 PM', '4:00 PM'];
                    if (!in_array($text, $validTimes)) {
                        $response = "Invalid time slot. Please select from the available times:\n" . implode("\n", $validTimes);
                        break;
                    }

                    // Create appointment
                    Appointment::create([
                        'user_id' => $session->temp_data['user_id'],
                        'appointment_date' => $session->temp_data['date'],
                        'appointment_time' => date('H:i:s', strtotime($text)),
                        'status' => 'scheduled'
                    ]);

                    $session->update(['current_step' => 'start', 'temp_data' => []]);
                    $response = "Perfect! Your appointment has been scheduled. We'll send you a confirmation email shortly.";
                    break;

                default:
                    $session->update(['current_step' => 'start']);
                    $response = "Welcome to our appointment booking system! Please enter your full name:";
            }

            Log::info('Sending response', [
                'phone' => $phone,
                'response' => $response
            ]);

            // Send response using WhatsApp API
            $result = $this->sendWhatsAppMessage($phone, $response);
            
            Log::info('WhatsApp API response', [
                'result' => $result
            ]);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error in handleMessage', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    private function sendWhatsAppMessage($to, $message)
    {
        $token = env('WHATSAPP_API_TOKEN');
        $phoneNumberId = env('WHATSAPP_PHONE_NUMBER_ID');

        $url = "https://graph.facebook.com/v12.0/{$phoneNumberId}/messages";
        
        $data = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => [
                'body' => $message
            ]
        ];

        $client = new Client();
        
        try {
            $response = $client->post($url, [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                    'Content-Type' => 'application/json',
                ],
                'json' => $data,
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('WhatsApp API Error: ' . $e->getMessage());
            return null;
        }
    }
}
