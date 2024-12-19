export interface User {
  id: number;
  name: string;
  email: string;
  phone_number: string;
  appointments: Appointment[];
}

export interface Appointment {
  id: number;
  user_id: number;
  user: {
    name: string;
    email: string;
    phone_number: string;
  };
  appointment_date: string;
  appointment_time: string;
  status: 'scheduled' | 'completed' | 'cancelled';
}
