import React, { useState, useEffect } from 'react';
import {
  Container,
  Box,
  Tabs,
  Tab,
  Typography,
  ThemeProvider,
  createTheme,
  CssBaseline
} from '@mui/material';
import UsersTable from './components/UsersTable';
import AppointmentsList from './components/AppointmentsList';
import { API_ROUTES } from './config/api';

const theme = createTheme({
  palette: {
    mode: 'light',
    primary: {
      main: '#2196f3',
    },
    background: {
      default: '#f5f5f5',
      paper: '#ffffff',
    },
  },
});

function App() {
  const [value, setValue] = useState(0);
  const [users, setUsers] = useState([]);
  const [appointments, setAppointments] = useState([]);
  const [loading, setLoading] = useState(true);

  const handleChange = (event: React.SyntheticEvent, newValue: number) => {
    setValue(newValue);
  };

  const getAppointments = async () => {
    try {
      setLoading(true);
      const response = await fetch(API_ROUTES.appointments);
      const data = await response.json();
      setAppointments(data);
    } catch (error) {
      console.error('Error fetching appointments:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    // Fetch users data
    fetch(API_ROUTES.users)
      .then(response => response.json())
      .then(data => setUsers(data))
      .catch(error => console.error('Error fetching users:', error));

    // Fetch appointments data
    getAppointments();
  }, []);

  return (
    <ThemeProvider theme={theme}>
      <CssBaseline />
      <Container maxWidth="lg" sx={{ py: 4 }}>
        <Typography variant="h4" component="h1" gutterBottom sx={{ color: 'primary.main', fontWeight: 'bold' }}>
          Appointment System
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
          {value === 0 && <UsersTable users={users} loading={loading} />}
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
              loading={loading}
            />
          )}
        </div>
      </Container>
    </ThemeProvider>
  );
}

export default App;
