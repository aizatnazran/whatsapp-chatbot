'use client';

import { useState, useEffect } from 'react';
import { User, Appointment } from '../types';
import { getUsers, getAppointments } from '../utils/api';
import {
  Container,
  Box,
  Tabs,
  Tab,
  Typography,
} from '@mui/material';
import UsersTable from '../components/UsersTable';
import AppointmentsList from '../components/AppointmentsList';

export default function Home() {
  const [value, setValue] = useState(0);
  const [users, setUsers] = useState<User[]>([]);
  const [appointments, setAppointments] = useState<Appointment[]>([]);

  const handleChange = (event: React.SyntheticEvent, newValue: number) => {
    setValue(newValue);
  };

  useEffect(() => {
    const fetchData = async () => {
      try {
        const [usersData, appointmentsData] = await Promise.all([
          getUsers(),
          getAppointments()
        ]);
        setUsers(usersData);
        setAppointments(appointmentsData);
      } catch (error) {
        console.error('Error fetching data:', error);
      }
    };

    fetchData();
  }, []);

  const updateAppointmentStatus = async (id: number, status: string) => {
    try {
      const updatedAppointments = await getAppointments();
      setAppointments(updatedAppointments);
    } catch (error) {
      console.error('Error updating appointment status:', error);
    }
  };

  return (
    <Container maxWidth="lg" sx={{ py: 4 }}>
      <Typography variant="h4" component="h1" gutterBottom sx={{ color: 'primary.main', fontWeight: 'bold' }}>
        WhatsApp Appointment System
      </Typography>

      <Box sx={{ borderBottom: 1, borderColor: 'divider', mb: 3 }}>
        <Tabs value={value} onChange={handleChange} aria-label="basic tabs example">
          <Tab 
            label={`Users (${users.length})`} 
            id="tab-0"
            aria-controls="tabpanel-0"
          />
          <Tab 
            label={`Appointments (${appointments.length})`} 
            id="tab-1"
            aria-controls="tabpanel-1"
          />
        </Tabs>
      </Box>

      <div
        role="tabpanel"
        hidden={value !== 0}
        id="tabpanel-0"
        aria-labelledby="tab-0"
      >
        {value === 0 && <UsersTable users={users} />}
      </div>

      <div
        role="tabpanel"
        hidden={value !== 1}
        id="tabpanel-1"
        aria-labelledby="tab-1"
      >
        {value === 1 && (
          <AppointmentsList 
            appointments={appointments} 
            setAppointments={setAppointments}
            updateAppointmentStatus={updateAppointmentStatus}
            getAppointments={getAppointments}
          />
        )}
      </div>
    </Container>
  );
}