import React, { useState } from 'react';
import {
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Paper,
  Dialog,
  DialogTitle,
  DialogContent,
  IconButton,
  Typography,
  Box,
  Chip,
  TextField,
  InputAdornment,
  Skeleton,
} from '@mui/material';
import { Close as CloseIcon } from '@mui/icons-material';
import { Search as SearchIcon } from '@mui/icons-material';

interface Appointment {
  id: number;
  appointment_date: string;
  appointment_time: string;
  status: 'scheduled' | 'completed' | 'cancelled';
}

interface User {
  id: number;
  name: string;
  email: string;
  phone_number: string;
  appointments: Appointment[];
}

const statusColors = {
  scheduled: '#2196f3',
  completed: '#4caf50',
  cancelled: '#f44336',
};

const UsersTable: React.FC<{ users: User[]; loading?: boolean }> = ({ users, loading = false }) => {
  const [selectedUser, setSelectedUser] = useState<User | null>(null);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [search, setSearch] = useState('');

  const handleAppointmentsClick = (user: User) => {
    setSelectedUser(user);
    setDialogOpen(true);
  };

  const handleClose = () => {
    setDialogOpen(false);
    setSelectedUser(null);
  };

  const formatDateTime = (date: string, time: string) => {

    const sanitizedDate = date.split('T')[0]; 
    const sanitizedTime = time.split('T')[1]?.split('.')[0]; 

    if (!sanitizedDate || !sanitizedTime) {
      console.error('Invalid date or time:', { sanitizedDate, sanitizedTime });
      return 'Invalid Date';
    }

    const combinedDateTime = `${sanitizedDate}T${sanitizedTime}`;

    // Parse and format
    const parsedDate = new Date(combinedDateTime);
    if (isNaN(parsedDate.getTime())) {
      console.error('Invalid Date Detected:', combinedDateTime);
      return 'Invalid Date';
    }

    const formattedDate = parsedDate.toLocaleDateString('en-US', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    });

    const hours = parsedDate.getHours();
    const minutes = parsedDate.getMinutes();
    const ampm = hours >= 12 ? 'PM' : 'AM';
    const hour12 = hours % 12 || 12;

    return `${formattedDate} at ${hour12}:${minutes.toString().padStart(2, '0')} ${ampm}`;
  };

  const filteredUsers = users.filter(
    (user) =>
      user.name.toLowerCase().includes(search.toLowerCase()) ||
      user.email.toLowerCase().includes(search.toLowerCase()) ||
      user.phone_number.toLowerCase().includes(search.toLowerCase())
  );

  return (
    <>
      <Box sx={{ mb: 3 }}>
        <TextField
          fullWidth
          placeholder="Search users by name, email, or phone number..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          InputProps={{
            startAdornment: (
              <InputAdornment position="start">
                <SearchIcon />
              </InputAdornment>
            ),
          }}
        />
      </Box>

      <TableContainer component={Paper} sx={{ boxShadow: 2, borderRadius: 2 }}>
        <Table>
          <TableHead>
            <TableRow sx={{ backgroundColor: '#f5f5f5' }}>
              <TableCell>
                <Typography variant="subtitle1" fontWeight="bold">
                  Name
                </Typography>
              </TableCell>
              <TableCell>
                <Typography variant="subtitle1" fontWeight="bold">
                  Email
                </Typography>
              </TableCell>
              <TableCell>
                <Typography variant="subtitle1" fontWeight="bold">
                  Phone Number
                </Typography>
              </TableCell>
              <TableCell>
                <Typography variant="subtitle1" fontWeight="bold">
                  Appointments
                </Typography>
              </TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            {loading ? (
              [...Array(5)].map((_, index) => (
                <TableRow key={index}>
                  <TableCell>
                    <Skeleton variant="text" width={150} />
                  </TableCell>
                  <TableCell>
                    <Skeleton variant="text" width={200} />
                  </TableCell>
                  <TableCell>
                    <Skeleton variant="text" width={120} />
                  </TableCell>
                  <TableCell>
                    <Skeleton variant="text" width={100} />
                  </TableCell>
                </TableRow>
              ))
            ) : (
              filteredUsers.map((user) => (
                <TableRow key={user.id} hover>
                  <TableCell>{user.name}</TableCell>
                  <TableCell>{user.email}</TableCell>
                  <TableCell>{user.phone_number}</TableCell>
                  <TableCell>
                    <Box
                      onClick={() => handleAppointmentsClick(user)}
                      sx={{
                        cursor: 'pointer',
                        color: '#2196f3',
                        '&:hover': { textDecoration: 'underline' },
                      }}
                    >
                      {user.appointments.length} appointments
                    </Box>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </TableContainer>

      <Dialog open={dialogOpen} onClose={handleClose} maxWidth="md" fullWidth>
        <DialogTitle>
          <Box display="flex" justifyContent="space-between" alignItems="center">
            <Typography variant="h6">{selectedUser?.name}'s Appointments</Typography>
            <IconButton onClick={handleClose} size="small">
              <CloseIcon />
            </IconButton>
          </Box>
        </DialogTitle>
        <DialogContent>
          <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2, py: 2 }}>
            {selectedUser?.appointments.map((appointment) => (
              <Paper
                key={appointment.id}
                sx={{
                  p: 2,
                  borderRadius: 2,
                  boxShadow: 1,
                  border: '1px solid #e0e0e0',
                }}
              >
                <Box display="flex" justifyContent="space-between" alignItems="center">
                  <Typography variant="body1">
                    {formatDateTime(appointment.appointment_date, appointment.appointment_time)}
                  </Typography>
                  <Chip
                    label={appointment.status.charAt(0).toUpperCase() + appointment.status.slice(1)}
                    sx={{
                      backgroundColor: statusColors[appointment.status],
                      color: 'white',
                    }}
                  />
                </Box>
              </Paper>
            ))}
          </Box>
        </DialogContent>
      </Dialog>
    </>
  );
};

export default UsersTable;
