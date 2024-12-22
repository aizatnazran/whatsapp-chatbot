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

export interface AppointmentsListProps {
  appointments: Appointment[];
  setAppointments: (appointments: Appointment[]) => void;
  updateAppointmentStatus: () => Promise<void>;
  getAppointments: () => Promise<Appointment[]>;
  loading?: boolean;
}

export interface UsersTableProps {
  users: User[];
  loading?: boolean;
}
