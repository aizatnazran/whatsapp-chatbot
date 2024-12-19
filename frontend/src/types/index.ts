export interface User {
  id: number;
  name: string;
  email: string;
  phone_number: string;
  appointments: Appointment[];
  created_at: string;
  updated_at: string;
}

export interface Appointment {
  id: number;
  user_id: number;
  appointment_date: string;
  appointment_time: string;
  status: 'scheduled' | 'completed' | 'cancelled';
  user?: User;
  created_at: string;
  updated_at: string;
}
