<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\ChatSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    private $whatsappToken;
    private $phoneNumberId;
    private $timeSlots = [
        1 => '9:00 AM',
        2 => '11:00 AM',
        3 => '2:00 PM',
        4 => '4:00 PM'
    ];

    public function __construct()
    {
        $this->whatsappToken = env('WHATSAPP_TOKEN');
        $this->phoneNumberId = env('PHONE_NUMBER_ID');

        if (empty($this->whatsappToken) || empty($this->phoneNumberId)) {
            Log::error('WhatsApp configuration missing', [
                'token_exists' => !empty($this->whatsappToken),
                'phone_number_id_exists' => !empty($this->phoneNumberId)
            ]);
        }
    }

    public function verifyWebhook(Request $request)
    {
        Log::info('Webhook verification request', [
            'params' => $request->all()
        ]);

        $mode = $request->input('hub_mode');
        $token = $request->input('hub_verify_token');
        $challenge = $request->input('hub_challenge');

        if ($mode === 'subscribe' && $token === env('VERIFY_TOKEN')) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    public function handleWebhook(Request $request)
    {
        Log::info('Webhook message received', [
            'payload' => $request->all()
        ]);

        try {
            $payload = $request->all();
            
            $messages = $payload['entry'][0]['changes'][0]['value']['messages'] ?? [];
            
            if (empty($messages)) {
                return response()->json(['status' => 'success', 'message' => 'No messages to process']);
            }

            foreach ($messages as $message) {
                $from = $message['from'] ?? null;
                $text = $message['text']['body'] ?? null;

                if ($from && $text) {
                    $this->processMessage($from, $text);
                }
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error processing webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    private function processMessage($from, $text)
    {
        try {
            Log::info('Processing message', [
                'from' => $from,
                'text' => $text
            ]);
    
            $normalizedFrom = $this->normalizePhoneNumber($from);
            
            $session = ChatSession::firstOrCreate(
                ['phone_number' => $normalizedFrom],
                ['current_step' => 'start', 'temp_data' => []]
            );
    
            $user = User::where('phone_number', $normalizedFrom)->first();
    
            // Add the EXIT command check here
            if (strtoupper(trim($text)) === 'EXIT') {
                $session->update([
                    'current_step' => 'start',
                    'temp_data' => []
                ]);
    
                if ($user) {
                    $response = "Welcome back {$user->name}!\n\nType 1 to create an appointment\nType 2 to view appointments";
                } else {
                    $response = "Welcome to our appointment booking system! Please enter your full name:";
                }
    
                return $this->sendWhatsAppMessage($normalizedFrom, $response);
            }
    
            // Handle returning user main menu
            if ($session->current_step === 'start') {
                if ($user) {
                    if (in_array($text, ['1', '2'])) {
                        if ($text === '1') {
                            $session->update([
                                'current_step' => 'appointment_date',
                                'temp_data' => [
                                    'name' => $user->name,
                                    'email' => $user->email,
                                    'phone' => $normalizedFrom
                                ]
                            ]);
                            $response = "Please select your preferred appointment date (YYYY-MM-DD):";
                        } else {
                            $appointments = Appointment::where('user_id', $user->id)->get();
                            if ($appointments->isEmpty()) {
                                $response = "You don't have any appointments scheduled.\n\nType 1 to create an appointment\nType 2 to view appointments";
                            } else {
                                $response = "Your appointments:\n\n";
                                foreach ($appointments as $apt) {
                                    $dayOfWeek = \Carbon\Carbon::parse($apt->appointment_date)->format('l'); // Get day
                                        $formattedDate = \Carbon\Carbon::parse($apt->appointment_date)->format('Y-m-d'); // Format without time
                                        $formattedTime = date('h:i A', strtotime($apt->appointment_time)); // Format time
                                        
                                        $response .= "Date: {$dayOfWeek}, {$formattedDate}\n";
                                        $response .= "Time: {$formattedTime}\n\n";
                                }
                                $response .= "Type 1 to create a new appointment\nType 2 to view appointments again";
                            }
                        }
                    } else {
                        $response = "Welcome back {$user->name}!\n\nType 1 to create an appointment\nType 2 to view appointments";
                    }
                } else {
                    $response = "Welcome to our appointment booking system! Please enter your full name:";
                    $session->update(['current_step' => 'name']);
                }
                return $this->sendWhatsAppMessage($normalizedFrom, $response);
            }
    
            // Handle first-time user or continue existing flow
            $response = '';

            switch ($session->current_step) {
                case 'name':
                    $session->update([
                        'temp_data' => array_merge($session->temp_data ?? [], ['name' => $text]),
                        'current_step' => isset($session->temp_data['editing']) ? 'confirm' : 'email'
                    ]);

                    if (isset($session->temp_data['editing'])) {
                        $data = $session->temp_data;
                        unset($data['editing']);
                        $session->update(['temp_data' => $data]);
                        $response = "Please review your information:\n\n";
                        $response .= "1. Name: {$data['name']}\n";
                        $response .= "2. Email: {$data['email']}\n";
                        $response .= "3. Phone: {$data['phone']}\n";
                        $response .= "4. Date: {$dayOfWeek}, {$data['date']}\n";
                        $response .= "5. Time: {$data['time']}\n\n";
                        $response .= "Is this correct? Type 'Yes' to confirm, or type the number (1-5) to modify that information.";
                    } else {
                        $response = "Great! Now please enter your email address:";
                    }
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
                        'current_step' => isset($session->temp_data['editing']) ? 'confirm' : 'phone'
                    ]);

                    if (isset($session->temp_data['editing'])) {
                        $data = $session->temp_data;
                        unset($data['editing']);
                        $session->update(['temp_data' => $data]);
                        $response = "Please review your information:\n\n";
                        $response .= "1. Name: {$data['name']}\n";
                        $response .= "2. Email: {$data['email']}\n";
                        $response .= "3. Phone: {$data['phone']}\n";
                        $response .= "4. Date: {$dayOfWeek}, {$data['date']}\n";
                        $response .= "5. Time: {$data['time']}\n\n";
                        $response .= "Is this correct? Type 'Yes' to confirm, or type the number (1-5) to modify that information.";
                    } else {
                        $response = "Perfect! Now please enter your phone number including country code:\nExample: +60123456789";
                    }
                    break;

                case 'phone':
                    // Normalize phone numbers for comparison
                    $inputNumber = $this->normalizePhoneNumber($text);
                    
                    // Compare normalized numbers
                    if ($inputNumber !== $normalizedFrom) {
                        $response = "The phone number must match the WhatsApp number you're messaging from ({$normalizedFrom}).\nPlease enter your phone number again:";
                        break;
                    }

                    $session->update([
                        'temp_data' => array_merge($session->temp_data ?? [], ['phone' => $normalizedFrom]),
                        'current_step' => isset($session->temp_data['editing']) ? 'confirm' : 'appointment_date'
                    ]);

                    if (isset($session->temp_data['editing'])) {
                        $data = $session->temp_data;
                        unset($data['editing']);
                        $session->update(['temp_data' => $data]);
                        $response = "Please review your information:\n\n";
                        $response .= "1. Name: {$data['name']}\n";
                        $response .= "2. Email: {$data['email']}\n";
                        $response .= "3. Phone: {$data['phone']}\n";
                        $response .= "4. Date: {$dayOfWeek}, {$data['date']}\n";
                        $response .= "5. Time: {$data['time']}\n\n";
                        $response .= "Is this correct? Type 'Yes' to confirm, or type the number (1-5) to modify that information.";
                    } else {
                        $response = "Great! Now please select your preferred appointment date (YYYY-MM-DD):";
                    }
                    break;

                    case 'appointment_date':
                        $validator = Validator::make(['date' => $text], [
                            'date' => 'required|date|after:today'
                        ]);
                    
                        if ($validator->fails()) {
                            $response = "Invalid date format or date is in the past. Please enter a future date (YYYY-MM-DD):";
                            break;
                        }
                    
                        $dayOfWeek = \Carbon\Carbon::parse($text)->format('l'); // Calculate day
                    
                        $availableSlots = $this->getAvailableTimeSlots($text);
                        if (empty($availableSlots)) {
                            $response = "Sorry, all time slots for {$dayOfWeek}, {$text} are fully booked. Please select a different date (YYYY-MM-DD):";
                            break;
                        }
                    
                        $session->update([
                            'temp_data' => array_merge($session->temp_data ?? [], [
                                'date' => $text,
                                'day' => $dayOfWeek,
                                'available_slots' => $availableSlots
                            ]),
                            'current_step' => 'appointment_time'
                        ]);
                    
                        $response = "Available time slots for {$dayOfWeek}, {$text}:\n";
                        foreach ($availableSlots as $index => $time) {
                            $response .= ($index + 1) . ". {$time}\n";
                        }
                        $response .= "\nPlease select a number from the available slots:";
                        break;

                        case 'appointment_time':
                            $availableSlots = $session->temp_data['available_slots'] ?? [];
                            $selectedIndex = (int)$text - 1;
                        
                            if (!isset($availableSlots[$selectedIndex])) {
                                $response = "Invalid selection. Please choose from:\n";
                                foreach ($availableSlots as $index => $time) {
                                    $response .= ($index + 1) . ". {$time}\n";
                                }
                                break;
                            }
                        
                            $selectedTime = $availableSlots[$selectedIndex];
                            $dayOfWeek = \Carbon\Carbon::parse($session->temp_data['date'])->format('l');
                            $session->update([
                                'temp_data' => array_merge($session->temp_data ?? [], ['time' => $selectedTime]),
                                'current_step' => 'confirm'
                            ]);
                        
                            $data = $session->temp_data;
                            $response = "Please review your information:\n\n";
                            $response .= "1. Name: {$data['name']}\n";
                            $response .= "2. Email: {$data['email']}\n";
                            $response .= "3. Phone: {$data['phone']}\n";
                            $response .= "4. Date: {$dayOfWeek}, {$data['date']}\n";
                            $response .= "5. Time: {$data['time']}\n\n";
                            $response .= "Is this correct? Type 'Yes' to confirm, or type the number (1-5) to modify that information.";
                            break;

                        case 'confirm':
                            if (strtolower($text) === 'yes') {
                                $data = $session->temp_data;
                        
                                // Create or update user
                                $user = User::updateOrCreate(
                                    ['phone_number' => $data['phone']],
                                    [
                                        'name' => $data['name'],
                                        'email' => $data['email'],
                                        'password' => bcrypt(Str::random(16))
                                    ]
                                );
                        
                                // Create appointment
                                Appointment::create([
                                    'user_id' => $user->id,
                                    'appointment_date' => $data['date'],
                                    'appointment_time' => date('H:i:s', strtotime($data['time'])),
                                    'status' => 'scheduled'
                                ]);
                        
                                $session->update(['current_step' => 'menu', 'temp_data' => []]);
                                $response = "Perfect! Your appointment has been scheduled.\n\nType 1 to create another appointment\nType 2 to view your appointments";
                            } elseif (in_array($text, ['1', '2', '3', '4', '5'])) {
                                $temp_data = $session->temp_data;
                        
                                switch ($text) {
                                    case '1': // Change name
                                        $session->update([
                                            'current_step' => 'name',
                                            'temp_data' => array_merge($temp_data, ['editing' => true])
                                        ]);
                                        $response = "Please enter your full name:";
                                        break;
                        
                                    case '2': // Change email
                                        $session->update([
                                            'current_step' => 'email',
                                            'temp_data' => array_merge($temp_data, ['editing' => true])
                                        ]);
                                        $response = "Please enter your email address:";
                                        break;
                        
                                    case '3': // Change phone number
                                        $session->update([
                                            'current_step' => 'phone',
                                            'temp_data' => array_merge($temp_data, ['editing' => true])
                                        ]);
                                        $response = "Please enter your phone number including country code:\nExample: +60123456789";
                                        break;
                        
                                    case '4': // Change date
                                    case '5': // Change time slot
                                        $session->update([
                                            'current_step' => 'appointment_date',
                                            'temp_data' => array_merge($temp_data, ['editing' => true])
                                        ]);
                                        $response = "Please select your preferred appointment date (YYYY-MM-DD):";
                                        break;
                        
                                    default:
                                        $response = "Invalid option. Please try again.";
                                }
                            } else {
                                $data = $session->temp_data;
    $dayOfWeek = \Carbon\Carbon::parse($data['date'])->format('l');
    $response = "Please review your information:\n\n";
    $response .= "1. Name: {$data['name']}\n";
    $response .= "2. Email: {$data['email']}\n";
    $response .= "3. Phone: {$data['phone']}\n";
    $response .= "4. Date: {$dayOfWeek}, {$data['date']}\n";
    $response .= "5. Time: {$data['time']}\n\n";
    $response .= "Is this correct? Type 'Yes' to confirm, or type the number (1-5) to modify that information.";
                            }
                            break;
                        

                case 'menu':
                    if ($text === '1') {
                        if ($user) {
                            $session->update([
                                'current_step' => 'appointment_date',
                                'temp_data' => [
                                    'name' => $user->name,
                                    'email' => $user->email,
                                    'phone' => $normalizedFrom
                                ]
                            ]);
                            $response = "Please select your preferred appointment date (YYYY-MM-DD):";
                        } else {
                            $session->update(['current_step' => 'name', 'temp_data' => []]);
                            $response = "Please enter your full name:";
                        }
                    } elseif ($text === '2') {
                            $user = User::where('phone_number', $normalizedFrom)->first();
                            if ($user) {
                                $appointments = Appointment::where('user_id', $user->id)->get();
                                if ($appointments->isEmpty()) {
                                    $response = "You don't have any appointments scheduled.\n\nType 1 to create an appointment\nType 2 to view appointments";
                                } else {
                                    $response = "Your appointments:\n\n";
                                    foreach ($appointments as $apt) {
                                        $dayOfWeek = \Carbon\Carbon::parse($apt->appointment_date)->format('l'); // Get day
                                        $formattedDate = \Carbon\Carbon::parse($apt->appointment_date)->format('Y-m-d'); // Format without time
                                        $formattedTime = date('h:i A', strtotime($apt->appointment_time)); // Format time
                                        
                                        $response .= "Date: {$dayOfWeek}, {$formattedDate}\n";
                                        $response .= "Time: {$formattedTime}\n\n";
                                    }
                                    $response .= "Type 1 to create a new appointment\nType 2 to view appointments again";
                                }
                            } else {
                                $session->update(['current_step' => 'name']);
                                $response = "No appointments found. Let's create one!\n\nPlease enter your full name:";
                            }
                        } else {
                            $response = "Invalid option. Type 1 to create an appointment or 2 to view your appointments.";
                        }
                        break;
            }

            if ($response) {
                $this->sendWhatsAppMessage($normalizedFrom, $response);
            }

        } catch (\Exception $e) {
            Log::error('Error in processMessage', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private function normalizePhoneNumber($number)
    {
        // Add + if missing
        if (!str_starts_with($number, '+')) {
            $number = '+' . $number;
        }
        return $number;
    }

    private function getAvailableTimeSlots($date)
    {
        // Fetch booked slots for the specific date
        $bookedSlots = Appointment::where('appointment_date', $date)
            ->pluck('appointment_time') // Returns times in 'H:i:s' format
            ->map(function ($time) {
                return date('g:i A', strtotime($time)); // Convert to 'h:i A' format
            })
            ->toArray();
    
        Log::info('Booked slots for date ' . $date, ['bookedSlots' => $bookedSlots]);
    
        // Filter available slots
        $availableSlots = [];
        foreach ($this->timeSlots as $index => $time) {
            if (!in_array($time, $bookedSlots)) {
                $availableSlots[] = $time;
            }
        }
    
        Log::info('Available slots for date ' . $date, ['availableSlots' => $availableSlots]);
    
        return $availableSlots; // Returns re-indexed array of available slots
    }

    private function sendWhatsAppMessage($to, $message)
    {
        try {
            if (empty($this->whatsappToken) || empty($this->phoneNumberId)) {
                throw new \Exception('WhatsApp configuration missing');
            }

            $url = "https://graph.facebook.com/v17.0/{$this->phoneNumberId}/messages";
            
            $data = [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'body' => $message
                ]
            ];

            Log::info('Sending WhatsApp message', [
                'to' => $to,
                'message' => $message,
                'url' => $url,
                'data' => $data
            ]);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->whatsappToken,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            Log::info('WhatsApp API response', [
                'response' => $response,
                'httpCode' => $httpCode,
                'error' => $error
            ]);

            if ($httpCode !== 200) {
                throw new \Exception("WhatsApp API error: HTTP $httpCode - $error");
            }

            return $response;
        } catch (\Exception $e) {
            Log::error('Error sending WhatsApp message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}
