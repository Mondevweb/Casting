import { defineStore } from 'pinia';
import api from '@/services/api';

export const useProfessionalStore = defineStore('professional', {
    state: () => ({
        professionals: [], // All pros
        currentProfessional: null,
        loading: false,
        error: null,
        filters: {
            search: '',
            jobTitles: [],
            specialties: []
        }
    }),
    getters: {
        filteredProfessionals: (state) => {
            let result = state.professionals;

            // Filter by search text (Name, City)
            if (state.filters.search) {
                const query = state.filters.search.toLowerCase();
                result = result.filter(pro =>
                    pro.firstName.toLowerCase().includes(query) ||
                    pro.lastName.toLowerCase().includes(query) ||
                    (pro.city && pro.city.toLowerCase().includes(query))
                );
            }

            // Filter by JobTitles (OR logic)
            if (state.filters.jobTitles && state.filters.jobTitles.length > 0) {
                const selectedJobTitleIds = state.filters.jobTitles.map(jt => jt.id);
                result = result.filter(pro =>
                    pro.jobTitle && selectedJobTitleIds.includes(pro.jobTitle.id)
                );
            }

            // Filter by Specialties (OR logic)
            if (state.filters.specialties && state.filters.specialties.length > 0) {
                const selectedSpecialtyIds = state.filters.specialties.map(s => s.id);
                result = result.filter(pro =>
                    pro.specialties && pro.specialties.some(spec => selectedSpecialtyIds.includes(spec.id))
                );
            }

            return result;
        }
    },
    actions: {
        async fetchProfessionals() {
            this.loading = true;
            this.error = null;
            try {
                // Pagination disabled in API Resource, so this returns ALL pros
                const response = await api.get('/professionals');

                if (Array.isArray(response.data)) {
                    this.professionals = response.data;
                } else if (response.data['hydra:member']) {
                    this.professionals = response.data['hydra:member'];
                } else {
                    this.professionals = [];
                }
            } catch (err) {
                this.error = 'Impossible de charger les professionnels.';
                console.error(err);
            } finally {
                this.loading = false;
            }
        },
        async fetchProfessional(id) {
            this.loading = true;
            this.error = null;
            try {
                const response = await api.get(`/professionals/${id}`);
                this.currentProfessional = response.data;
            } catch (err) {
                this.error = 'Impossible de charger le professionnel.';
                console.error(err);
            } finally {
                this.loading = false;
            }
        },
        updateSearch(query) {
            this.filters.search = query;
        },
        updateJobTitles(jobTitles) {
            this.filters.jobTitles = jobTitles;
        },
        updateSpecialties(specialties) {
            this.filters.specialties = specialties;
        }
    }
});
