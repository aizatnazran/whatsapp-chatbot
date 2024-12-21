const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL;

if (!API_BASE_URL) {
    throw new Error('NEXT_PUBLIC_API_URL environment variable is not set');
}

export const API_ROUTES = {
    appointments: `${API_BASE_URL}/api/appointments`,
    users: `${API_BASE_URL}/api/users`,
};
