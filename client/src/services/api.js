import axios from 'axios';

// Create Axios instance
export const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'https://127.0.0.1:8000';

const api = axios.create({
    baseURL: `${API_BASE_URL}/api`, // Utilisation de l'URL environnée
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
