import axios from 'axios';

// Create Axios instance
const api = axios.create({
    baseURL: 'https://127.0.0.1:8000/api', // Local Symfony Server (HTTPS)
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    },
    // withCredentials: true // Removed to fix CORS error with JWT auth (no cookies needed)
});

// Add interceptor for JWT
api.interceptors.request.use(config => {
    const token = localStorage.getItem('token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

export default api;
