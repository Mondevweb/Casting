import { defineStore } from 'pinia';
import api from '@/services/api';

export const useSpecialtyStore = defineStore('specialty', {
    state: () => ({
        specialties: [],
        loading: false,
        error: null
    }),
    actions: {
        async fetchSpecialties() {
            this.loading = true;
            this.error = null;
            try {
                const response = await api.get('/specialties');
                if (Array.isArray(response.data)) {
                    this.specialties = response.data;
                } else if (response.data['hydra:member']) {
                    this.specialties = response.data['hydra:member'];
                } else {
                    this.specialties = [];
                }
            } catch (err) {
                this.error = 'Impossible de charger les spécialités.';
                console.error(err);
            } finally {
                this.loading = false;
            }
        }
    }
});
