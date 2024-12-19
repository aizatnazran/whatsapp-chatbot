import { Appointment } from '../types';

interface AppointmentCardProps {
  appointment: Appointment;
}

export default function AppointmentCard({ appointment }: AppointmentCardProps) {
  const statusColors = {
    scheduled: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
  };

  return (
    <div className="bg-white rounded-lg shadow-md p-6 mb-4">
      <div className="flex justify-between items-start mb-4">
        <div>
          <h3 className="text-xl font-semibold">{appointment.user?.name}</h3>
          <p className="text-gray-600">{appointment.user?.email}</p>
        </div>
        <span className={`px-3 py-1 rounded-full text-sm font-medium ${statusColors[appointment.status]}`}>
          {appointment.status}
        </span>
      </div>
      <div className="text-gray-600">
        <p>Date: {new Date(appointment.appointment_date).toLocaleDateString()}</p>
        <p>Time: {appointment.appointment_time}</p>
      </div>
    </div>
  );
}
