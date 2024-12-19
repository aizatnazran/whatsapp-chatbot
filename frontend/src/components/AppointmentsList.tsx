import React, { useState } from 'react';
import {
  Box,
  Card,
  CardContent,
  Typography,
  Chip,
  TextField,
  Select,
  MenuItem,
  FormControl,
  InputLabel,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Button,
  IconButton,
  InputAdornment
} from '@mui/material';
import { Search as SearchIcon } from '@mui/icons-material';

interface Appointment {
  id: number;
  user: {
    name: string;
    email: string;
    phone_number: string;
  };
  appointment_date: string;
  appointment_time: string;
  status: 'scheduled' | 'completed' | 'cancelled';
}

const statusColors = {
  scheduled: '#2196f3',
  completed: '#4caf50',
  cancelled: '#f44336'
};

const AppointmentsList: React.FC<{ appointments: Appointment[]; setAppointments: (appointments: Appointment[]) => void; updateAppointmentStatus: (id: number, status: 'scheduled' | 'completed' | 'cancelled') => Promise<void>; getAppointments: () => Promise<Appointment[]> }> = ({ appointments, setAppointments, updateAppointmentStatus, getAppointments }) => {
  const [filter, setFilter] = useState('all');
  const [search, setSearch] = useState('');
  const [selectedAppointment, setSelectedAppointment] = useState<Appointment | null>(null);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [newStatus, setNewStatus] = useState<string>('');

  const handleStatusChange = async () => {
    try {
      await updateAppointmentStatus(selectedAppointment?.id!, newStatus as 'scheduled' | 'completed' | 'cancelled');
      // Refresh appointments list
      const updatedAppointments = await getAppointments();
      setAppointments(updatedAppointments);
      setDialogOpen(false);
    } catch (error) {
      console.error('Error updating appointment status:', error);
      // You might want to show an error message to the user here
    }
  };

  const formatDateTime = (date: string, time: string) => {
    const formattedDate = new Date(date).toLocaleDateString('en-US', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
    const formattedTime = new Date(`2000-01-01 ${time}`).toLocaleTimeString('en-US', {
      hour: 'numeric',
      minute: 'numeric',
      hour12: true
    });
    return `${formattedDate} at ${formattedTime}`;
  };

  const filteredAppointments = appointments
    .filter(apt => filter === 'all' || apt.status === filter)
    .filter(apt => 
      apt.user.name.toLowerCase().includes(search.toLowerCase()) ||
      formatDateTime(apt.appointment_date, apt.appointment_time).toLowerCase().includes(search.toLowerCase())
    )
    .sort((a, b) => new Date(a.appointment_date).getTime() - new Date(b.appointment_date).getTime());

  return (
    <Box sx={{ p: 3 }}>
      <Box sx={{ mb: 3, display: 'flex', gap: 2 }}>
        <TextField
          placeholder="Search appointments..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          sx={{ flexGrow: 1 }}
          InputProps={{
            startAdornment: (
              <InputAdornment position="start">
                <SearchIcon />
              </InputAdornment>
            ),
          }}
        />
        <FormControl sx={{ minWidth: 200 }}>
          <InputLabel>Filter Status</InputLabel>
          <Select
            value={filter}
            label="Filter Status"
            onChange={(e) => setFilter(e.target.value)}
          >
            <MenuItem value="all">All</MenuItem>
            <MenuItem value="scheduled">Scheduled</MenuItem>
            <MenuItem value="completed">Completed</MenuItem>
            <MenuItem value="cancelled">Cancelled</MenuItem>
          </Select>
        </FormControl>
      </Box>

      <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
        {filteredAppointments.map((appointment) => (
          <Card
            key={appointment.id}
            sx={{
              cursor: 'pointer',
              '&:hover': { boxShadow: 3 },
              borderRadius: 2
            }}
            onClick={() => {
              setSelectedAppointment(appointment);
              setNewStatus(appointment.status);
              setDialogOpen(true);
            }}
          >
            <CardContent>
              <Box display="flex" justifyContent="space-between" alignItems="center">
                <Box>
                  <Typography variant="h6" gutterBottom>
                    {appointment.user.name}
                  </Typography>
                  <Typography variant="body2" color="text.secondary">
                    {formatDateTime(appointment.appointment_date, appointment.appointment_time)}
                  </Typography>
                </Box>
                <Chip
                  label={appointment.status.charAt(0).toUpperCase() + appointment.status.slice(1)}
                  sx={{
                    backgroundColor: statusColors[appointment.status],
                    color: 'white'
                  }}
                />
              </Box>
            </CardContent>
          </Card>
        ))}
      </Box>

      <Dialog open={dialogOpen} onClose={() => setDialogOpen(false)}>
        <DialogTitle>Update Appointment Status</DialogTitle>
        <DialogContent>
          <Box sx={{ mt: 2 }}>
            <FormControl fullWidth>
              <InputLabel>Status</InputLabel>
              <Select
                value={newStatus}
                label="Status"
                onChange={(e) => setNewStatus(e.target.value)}
              >
                <MenuItem value="scheduled">Scheduled</MenuItem>
                <MenuItem value="completed">Completed</MenuItem>
                <MenuItem value="cancelled">Cancelled</MenuItem>
              </Select>
            </FormControl>
          </Box>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setDialogOpen(false)}>Cancel</Button>
          <Button onClick={handleStatusChange} variant="contained">
            Update
          </Button>
        </DialogActions>
      </Dialog>
    </Box>
  );
};

export default AppointmentsList;