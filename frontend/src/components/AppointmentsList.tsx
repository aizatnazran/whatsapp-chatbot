import React, { useState, useEffect } from 'react';
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
  InputAdornment,
  Skeleton,
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
  cancelled: '#f44336',
};

const AppointmentsList: React.FC<{
  appointments: Appointment[];
  setAppointments: (appointments: Appointment[]) => void;
  loading?: boolean;
}> = ({ appointments, setAppointments, loading = false }) => {
  const [filter, setFilter] = useState('all');
  const [search, setSearch] = useState('');
  const [selectedAppointment, setSelectedAppointment] = useState<Appointment | null>(null);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [newStatus, setNewStatus] = useState<string>('');

  const getAppointments = async () => {
    try {
      const response = await fetch('http://localhost:8000/api/appointments');
      const data = await response.json();
      setAppointments(data);
    } catch (error) {
      console.error('Error fetching appointments:', error);
    }
  };

  useEffect(() => {
    getAppointments();
  }, []);

  const handleStatusChange = async () => {

    if (!selectedAppointment || !newStatus) {
      console.error('No appointment or status selected');
      return;
    }

    try {

      const response = await fetch(`http://localhost:8000/api/appointments/${selectedAppointment.id}/status`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({ status: newStatus }),
      });

      const responseData = await response.text();

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}, body: ${responseData}`);
      }

      setDialogOpen(false);
      
      // Refresh the appointments list
      await getAppointments();
    } catch (error) {
      console.error('Error in handleStatusChange:', error);
    }
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

  const filteredAppointments = appointments
    .filter((apt) => filter === 'all' || apt.status === filter)
    .filter(
      (apt) =>
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
          <Select value={filter} label="Filter Status" onChange={(e) => setFilter(e.target.value)}>
            <MenuItem value="all">All</MenuItem>
            <MenuItem value="scheduled">Scheduled</MenuItem>
            <MenuItem value="completed">Completed</MenuItem>
            <MenuItem value="cancelled">Cancelled</MenuItem>
          </Select>
        </FormControl>
      </Box>

      <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
        {loading ? (
          [...Array(5)].map((_, index) => (
            <Card key={index} sx={{ borderRadius: 2 }}>
              <CardContent>
                <Box display="flex" justifyContent="space-between" alignItems="center">
                  <Box>
                    <Skeleton variant="text" width={200} height={32} />
                    <Skeleton variant="text" width={300} height={24} />
                  </Box>
                  <Skeleton variant="rounded" width={100} height={32} />
                </Box>
              </CardContent>
            </Card>
          ))
        ) : (
          filteredAppointments.map((appointment) => (
            <Card
              key={appointment.id}
              sx={{
                cursor: 'pointer',
                '&:hover': { boxShadow: 3 },
                borderRadius: 2,
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
                      color: 'white',
                    }}
                  />
                </Box>
              </CardContent>
            </Card>
          ))
        )}
      </Box>

      <Dialog open={dialogOpen} onClose={() => setDialogOpen(false)}>
        <DialogTitle>Update Appointment Status</DialogTitle>
        <DialogContent>
          <Box sx={{ mt: 2 }}>
            <FormControl fullWidth>
              <InputLabel>Status</InputLabel>
              <Select value={newStatus} label="Status" onChange={(e) => setNewStatus(e.target.value)}>
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
