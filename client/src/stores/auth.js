import { defineStore } from 'pinia';
import api from '@/services/api';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: JSON.parse(localStorage.getItem('user')) || null,
        token: localStorage.getItem('token') || null,
        returnUrl: null
    }),
    getters: {
        isAuthenticated: (state) => !!state.token
    },
    actions: {
        async login(email, password) {
            try {
                // Correct endpoint for LexikJWT
                // Correct endpoint for LexikJWT
                const response = await api.post('/login_check', { email, password });

                const { token } = response.data;
                this.token = token;
                localStorage.setItem('token', token);

                // Decode token to get basic user info (email, roles)
                // JWT payload: { "username": "...", "roles": [...], "iat": ..., "exp": ... }
                const payload = JSON.parse(atob(token.split('.')[1]));

                this.user = {
                    email: payload.username,
                    roles: payload.roles
                };
                localStorage.setItem('user', JSON.stringify(this.user));

                // Optional: Fetch full user profile if needed
                // await this.fetchUserProfile();

                return true;
            } catch (error) {
                console.error('Login failed', error);
                throw error;
            }
        },
        async register(data) {
            try {
                // Adjust to match your User/Candidate creation endpoint
                // Typically POST /users or POST /candidates
                // Here assuming a generic registration for now
                await api.post('/users', data);
                return true;
            } catch (error) {
                console.error('Registration failed', error);
                throw error;
            }
        },
        logout() {
            this.user = null;
            this.token = null;
            this.returnUrl = null;
            localStorage.removeItem('user');
            localStorage.removeItem('token');
            // Call API logout if needed
            // api.post('/logout'); 
        }
    }
});
