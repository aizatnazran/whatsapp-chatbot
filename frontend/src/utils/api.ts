import { User, Appointment } from '../types';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';

export async function getUsers(): Promise<User[]> {
  const response = await fetch(`${API_BASE_URL}/api/users`);
  if (!response.ok) {
    throw new Error('Failed to fetch users');
  }
  return response.json();
}

export async function getAppointments(): Promise<Appointment[]> {
  const response = await fetch(`${API_BASE_URL}/api/appointments`);
  if (!response.ok) {
    throw new Error('Failed to fetch appointments');
  }
  return response.json();
}

export async function updateAppointmentStatus(
  appointmentId: number,
  status: 'scheduled' | 'completed' | 'cancelled'
): Promise<void> {
  const response = await fetch(`${API_BASE_URL}/api/appointments/${appointmentId}/status`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ status }),
  });

  if (!response.ok) {
    throw new Error('Failed to update appointment status');
  }
}
