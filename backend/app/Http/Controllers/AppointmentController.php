<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index()
    {
        $appointments = Appointment::with('user')
            ->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc')
            ->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'user' => [
                        'name' => $appointment->user->name,
                        'email' => $appointment->user->email,
                        'phone_number' => $appointment->user->phone_number,
                    ],
                    'appointment_date' => $appointment->appointment_date,
                    'appointment_time' => $appointment->appointment_time,
                    'status' => $appointment->status,
                ];
            });

        return response()->json($appointments);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:scheduled,completed,cancelled'
        ]);

        $appointment = Appointment::findOrFail($id);
        $appointment->status = $request->status;
        $appointment->save();

        return response()->json(['message' => 'Status updated successfully']);
    }
}
