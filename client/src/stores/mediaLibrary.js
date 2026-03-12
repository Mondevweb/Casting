import { defineStore } from 'pinia';
import api from '@/services/api';

export const useMediaLibraryStore = defineStore('mediaLibrary', {
    state: () => ({
        medias: [],
        serviceTypes: [],
        loading: false,
        error: null,
    }),
    getters: {
        getMediaByServiceType: (state) => (serviceTypeId) => {
            const targetId = parseInt(serviceTypeId, 10);
            return state.medias.filter(media => {
                if (!media.serviceType) return false;
                
                let mediaServiceId;
                if (typeof media.serviceType === 'object') {
                    mediaServiceId = media.serviceType.id 
                                     || parseInt(media.serviceType['@id'].split('/').pop(), 10);
                } else if (typeof media.serviceType === 'string') {
                    mediaServiceId = parseInt(media.serviceType.split('/').pop(), 10);
                } else {
                    mediaServiceId = parseInt(media.serviceType, 10);
                }
                
                return mediaServiceId === targetId;
            });
        }
    },
    actions: {
        async fetchServiceTypes() {
            try {
                const response = await api.get('/service_types');
                if (response.data['hydra:member']) {
                    this.serviceTypes = response.data['hydra:member'];
                } else if (Array.isArray(response.data)) {
                    this.serviceTypes = response.data;
                }
            } catch (err) {
                console.error("Failed to fetch service types for library", err);
            }
        },

        async fetchLibrary() {
            this.loading = true;
            this.error = null;
            try {
                // Fetch candidate's own media objects
                // Security rule ensures they only see their own
                const response = await api.get('/media_objects', {
                    params: { _t: new Date().getTime() }
                });

                if (response.data['hydra:member']) {
                    this.medias = response.data['hydra:member'];
                } else if (Array.isArray(response.data)) {
                    this.medias = response.data;
                } else {
                    this.medias = [];
                }
            } catch (err) {
                console.error("Failed to fetch media library", err);
                this.error = "Impossible de charger votre médiathèque.";
            } finally {
                this.loading = false;
            }
        },

        async deleteMedia(mediaId) {
            this.loading = true;
            this.error = null;
            try {
                // Appelle le endpoint DELETE qui va passer 'deletedAt' à NOW() grâce à l'extension Gedmo SoftDeleteable
                await api.delete(`/media_objects/${mediaId}`);
                // Remove locally
                this.medias = this.medias.filter(m => m.id !== mediaId);
            } catch (err) {
                console.error("Failed to delete media", err);
                this.error = "Impossible de supprimer le média.";
                throw err;
            } finally {
                this.loading = false;
            }
        }
    }
});
