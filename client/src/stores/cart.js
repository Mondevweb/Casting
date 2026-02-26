import { defineStore } from 'pinia';
import api from '@/services/api';

export const useCartStore = defineStore('cart', {
    state: () => ({
        items: JSON.parse(localStorage.getItem('cartItems')) || []
    }),
    getters: {
        totalItems: (state) => state.items.length,
        totalAmount: (state) => state.items.reduce((sum, item) => sum + item.price, 0)
    },
    actions: {
        addToCart(service) {
            this.items.push(service);
            localStorage.setItem('cartItems', JSON.stringify(this.items));
        },
        removeFromCart(index) {
            this.items.splice(index, 1);
            localStorage.setItem('cartItems', JSON.stringify(this.items));
        },
        clearCart() {
            this.items = [];
            localStorage.setItem('cartItems', JSON.stringify(this.items));
        },
        async checkout() {
            // Group items by Professional
            const ordersByPro = {};

            this.items.forEach(item => {
                const proId = item.professional.id;
                if (!ordersByPro[proId]) {
                    ordersByPro[proId] = {
                        professional: `/api/professionals/${proId}`,
                        orderLines: []
                    };
                }

                ordersByPro[proId].orderLines.push({
                    service: `/api/pro_services/${item.id}`,
                    quantityBilled: 1, // Default quantity for now
                    // instructions: "..." // user input later
                });
            });

            // Send one request per professional
            // Note: In a real app, we might want a batch endpoint or a transaction
            const promises = Object.values(ordersByPro).map(orderPayload => {
                return api.post('/orders', orderPayload);
            });

            try {
                await Promise.all(promises);
                this.clearCart();
                return true;
            } catch (error) {
                console.error("Checkout failed", error);
                throw error;
            }
        }
    }
});
