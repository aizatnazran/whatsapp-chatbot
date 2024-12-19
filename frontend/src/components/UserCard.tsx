import { User } from '../types';

interface UserCardProps {
  user: User;
}

export default function UserCard({ user }: UserCardProps) {
  return (
    <div className="bg-white rounded-lg shadow-md p-6 mb-4">
      <h3 className="text-xl font-semibold mb-2">{user.name}</h3>
      <div className="text-gray-600">
        <p>Email: {user.email}</p>
        <p>Phone: {user.phone_number}</p>
        <p>Appointments: {user.appointments?.length || 0}</p>
      </div>
    </div>
  );
}
