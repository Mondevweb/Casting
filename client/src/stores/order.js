import { defineStore } from 'pinia';
import api from '@/services/api';

export const useOrderStore = defineStore('order', {
    state: () => ({
        orders: [],
        loading: false,
        error: null
    }),
    actions: {
        async fetchOrders() {
            this.loading = true;
            this.error = null;
            try {
                // The backend automatically filters orders for the current user
                const response = await api.get('/orders');

                // Handle both simple JSON array and Hydra Collection
                if (Array.isArray(response.data)) {
                    this.orders = response.data;
                } else if (response.data['hydra:member']) {
                    this.orders = response.data['hydra:member'];
                } else {
                    this.orders = [];
                }
            } catch (err) {
                this.error = 'Impossible de charger les commandes.';
                console.error(err);
            } finally {
                this.loading = false;
            }
        }
    }
});
