import { defineStore } from 'pinia';
import api from '@/services/api';
import { useCartStore } from './cart';

export const useOrderEditorStore = defineStore('orderEditor', {
    state: () => ({
        service: null,
        professional: null,
        existingItem: null, // The related cart item if it exists

        instructions: '',
        medias: [], // Single source of truth for media objects (optimistic UI)
        deletedMediaIris: [], // Anti-ghosting array

        isSaving: false,
        saveTimeout: null,
        savePromise: Promise.resolve(), // Queue mechanism for sequential saves
        
        pollingInterval: null, // Timer pour vérifier le statut de conversion FFmpeg
    }),

    actions: {
        /**
         * Initialize the editor when the modal opens.
         * Extracts data from the existing cart item or sets up empty state.
         */
        init(service, professional, cartItem = null) {
            this.service = service;
            this.professional = professional;
            this.existingItem = cartItem;

            // Reset state
            this.deletedMediaIris = [];

            if (cartItem) {
                this.instructions = cartItem.instructions || '';
                this.medias = cartItem.mediaObjects ? [...cartItem.mediaObjects] : [];
            } else {
                this.instructions = '';
                this.medias = [];
            }
            
            this.startPolling();
        },

        /**
         * Add newly uploaded media to the optimistic array and trigger auto-save.
         */
        addMedia(mediaObjects) {
            const arr = Array.isArray(mediaObjects) ? mediaObjects : [mediaObjects];

            // Add robust IRI extraction
            const getIri = (m) => typeof m === 'string' ? m : (m['@id'] || (m.id ? `/api/media_objects/${m.id}` : null));
            const currentIris = this.medias.map(getIri).filter(Boolean);

            arr.forEach(media => {
                const iri = getIri(media);
                if (iri && !currentIris.includes(iri) && !this.deletedMediaIris.includes(iri)) {
                    this.medias.push(media);
                } else if (!iri) {
                    // Fallback for objects that don't have IRI yet (should not happen with good backends, but just in case)
                    this.medias.push(media);
                }
            });

            this.queueAutoSave();
            this.startPolling();
        },

        /**
         * Remove media from the optimistic array, remember its deletion to prevent ghosting,
         * delete it from the server, and trigger auto-save.
         */
        removeMedia(index) {
            const mediaObj = this.medias[index];
            if (!mediaObj) return;

            // Remove from local array
            this.medias.splice(index, 1);

            // Add to anti-ghosting ledger
            const iri = typeof mediaObj === 'string' ? mediaObj : (mediaObj['@id'] || (mediaObj.id ? `/api/media_objects/${mediaObj.id}` : null));
            if (iri) {
                this.deletedMediaIris.push(iri);
            }

            // Physically delete from server (non-blocking)
            if (iri) {
                // Axios est configuré avec baseURL: '/api', on retire '/api/' de l'IRI
                const endpoint = iri.startsWith('/api/') ? iri.substring(5) : (iri.startsWith('/api') ? iri.substring(4) : iri);
                api.delete(endpoint)
                    .then(() => console.log('Média supprimé physiquement du serveur'))
                    .catch(e => console.error('Impossible de supprimer le média', e));
            }

            this.queueAutoSave();
        },

        /**
         * Update instructions and debounce save.
         */
        updateInstructions(text) {
            this.instructions = text;

            clearTimeout(this.saveTimeout);
            this.saveTimeout = setTimeout(() => {
                this.queueAutoSave();
            }, 1000);
        },

        /**
         * Clear intervals and states on visual close.
         * Ensures any pending saves are flushed to the server before destroying state.
         */
        async clear() {
            this.stopPolling();
            
            // S'il y avait une sauvegarde en attente (debounce timeout non écoulé), on force l'enregistrement
            if (this.saveTimeout) {
                clearTimeout(this.saveTimeout);
                this.saveTimeout = null;
                console.log("Forcing graceful save before clearing Editor store...");
                await this.performSave();
            }

            // Attendre que d'éventuelles sauvegardes en cours (savePromise) se terminent
            try {
                await this.savePromise;
            } catch (e) {
                // Ignore queue errors on closure
            }

            this.deletedMediaIris = [];

            // Réinitialisation absolue de la mémoire d'édition pour éviter les fuites (state leakage) entre Services
            this.service = null;
            this.professional = null;
            this.existingItem = null;
            this.instructions = '';
            this.medias = [];
        },

        /**
         * Queue an auto-save operation to ensure sequential API patches.
         */
        queueAutoSave() {
            this.isSaving = true;
            // Chain the next save operation to the promise queue
            this.savePromise = this.savePromise
                .then(() => this.performSave())
                .catch(err => {
                    console.error("Erreur dans la file d'attente de sauvegarde:", err);
                    // Recover queue
                    return Promise.resolve();
                })
                .finally(() => {
                    this.isSaving = false;
                });
        },

        /**
         * The actual API call logic for saving.
         */
        async performSave() {
            if (!this.service || !this.professional) {
                console.warn("Auto-save annulée : L'éditeur a déjà été fermé et réinitialisé.");
                return;
            }

            const cartStore = useCartStore();

            // Build the final array of IRIs to send
            const finalMediaIds = this.medias.map(m => {
                if (typeof m === 'string') return m;
                return m['@id'] || (m.id ? `/api/media_objects/${m.id}` : null);
            }).filter(Boolean);

            const payload = {
                ...this.service,
                professional: {
                    id: this.professional.id,
                    firstName: this.professional.firstName,
                    lastName: this.professional.lastName,
                },
                // Pass flags so cartStore knows this is a direct replacement
                isEdit: true,
                instructions: this.instructions,
                mediaObjects: finalMediaIds,
            };

            console.log("🧨 EDITOR STORE SAVE:", payload);

            try {
                // Tell the cart store to process the payload
                await cartStore.addToCart(payload);
                // Refresh the global cart state to update badges, etc.
                await cartStore.fetchCart();

                // Update our existingItem reference if the cartStore created a new one or modified it
                // We find it the same way the modal used to look for dynamicExistingItem
                const serviceId = this.service.id;
                const proId = this.professional.id;
                this.existingItem = cartStore.allItems.find(item =>
                    (item.service === `/api/pro_services/${serviceId}` ||
                        (item.service && item.service['@id'] === `/api/pro_services/${serviceId}`) ||
                        (item.service && item.service.id === serviceId)) &&
                    (item.professional && (item.professional.id === proId || item.professional['@id'] === `/api/professionals/${proId}`))
                ) || this.existingItem;

            } catch (error) {
                console.error("Échec de la sauvegarde par le Store Éditeur", error);
                throw error; // Bubble up for potential toast handling in UI
            }
        },

        /**
         * Lance une boucle de vérification pour actualiser l'état des vidéos en cours de conversion.
         */
        startPolling() {
            if (this.pollingInterval) return;
            this.pollingInterval = setInterval(async () => {
                let needsPolling = false;
                for (let i = 0; i < this.medias.length; i++) {
                    const m = this.medias[i];
                    if (m && (m.transcodingStatus === 'PENDING' || m.transcodingStatus === 'PROCESSING')) {
                        needsPolling = true;
                        try {
                            // La base d'Axios inclut déjà '/api' (/api/media_objects/:id)
                            const res = await api.get(`/media_objects/${m.id}`);
                            if (res.data) {
                                // Remplacement réactif : on fusionne les nouvelles données avec l'objet actuel
                                this.medias.splice(i, 1, { ...m, ...res.data });
                            }
                        } catch (e) {
                            console.warn("Impossible de vérifier l'avancement de la vidéo", e);
                        }
                    }
                }
                // S'il n'y a plus aucun média en "PENDING/PROCESSING", on éteint la boucle
                if (!needsPolling) {
                    this.stopPolling();
                }
            }, 3000); // Check toutes les 3 secondes
        },

        stopPolling() {
            if (this.pollingInterval) {
                clearInterval(this.pollingInterval);
                this.pollingInterval = null;
            }
        }
    }
});
