import { defineStore } from 'pinia';
import api from '@/services/api';

export const useJobTitleStore = defineStore('jobTitle', {
    state: () => ({
        jobTitles: [],
        loading: false,
        error: null
    }),
    actions: {
        async fetchJobTitles() {
            this.loading = true;
            this.error = null;
            try {
                const response = await api.get('/job_titles');
                if (Array.isArray(response.data)) {
                    this.jobTitles = response.data;
                } else if (response.data['hydra:member']) {
                    this.jobTitles = response.data['hydra:member'];
                } else {
                    this.jobTitles = [];
                }
            } catch (err) {
                this.error = 'Impossible de charger les métiers.';
                console.error(err);
            } finally {
                this.loading = false;
            }
        }
    }
});
