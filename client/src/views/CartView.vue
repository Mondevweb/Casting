<template>
  <div class="min-h-screen bg-gray-100 pb-10">
    <nav class="bg-white shadow">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
           <div class="flex items-center">
             <Button icon="pi pi-arrow-left" class="p-button-text mr-4" @click="$router.push('/dashboard')" />
             <div class="font-bold text-xl text-indigo-600">
               Mon Panier
             </div>
           </div>
        </div>
      </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
        <div v-if="cartItems.length > 0" class="flex flex-col lg:flex-row gap-6">
            <!-- List Items -->
            <div class="flex-1">
                <div v-for="(item, index) in cartItems" :key="index" class="bg-white shadow rounded-lg p-6 mb-4 flex justify-between items-center">
                    <div>
                         <h3 class="text-lg font-semibold text-gray-900">{{ item.serviceType?.name }} - {{ item.professional.firstName }} {{ item.professional.lastName }}</h3>
                         <p class="text-gray-500">{{ item.serviceType?.description }}</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="font-bold text-lg">{{ formatCurrency(item.basePrice) }}</span>
                        <Button icon="pi pi-trash" class="p-button-danger p-button-text" @click="removeItem(index)" />
                    </div>
                </div>
            </div>

            <!-- Summary Sidebar -->
            <div class="w-full lg:w-80">
                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-xl font-bold mb-4">Récapitulatif</h2>
                    <div class="flex justify-between mb-2">
                        <span>Articles ({{ totalItems }})</span>
                        <span>{{ formatCurrency(totalAmount) }}</span>
                    </div>
                    <hr class="my-4" />
                    <div class="flex justify-between mb-6 text-xl font-bold">
                        <span>Total</span>
                        <span>{{ formatCurrency(totalAmount) }}</span>
                    </div>
                    
                    <Button label="Valider la commande" class="w-full" :loading="loading" @click="handleCheckout" />
                    
                    <div v-if="error" class="mt-4 text-red-500 text-center text-sm">
                        {{ error }}
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="text-center mt-20">
            <i class="pi pi-shopping-cart text-6xl text-gray-300 mb-4"></i>
            <p class="text-gray-500 text-xl">Votre panier est vide.</p>
            <Button label="Parcourir le catalogue" class="mt-4" @click="$router.push('/catalog')" />
        </div>
    </div>
  </div>
</template>

<script>
import { useCartStore } from '@/stores/cart';
import Button from 'primevue/button';

export default {
    components: {
        Button
    },
    data() {
        return {
            cartStore: useCartStore(),
            loading: false,
            error: ''
        };
    },
    computed: {
        cartItems() {
            return this.cartStore.items;
        },
        totalItems() {
            return this.cartStore.totalItems;
        },
        totalAmount() {
            return this.cartStore.totalAmount;
        }
    },
    methods: {
        formatCurrency(value) {
            return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value / 100);
        },
        removeItem(index) {
            this.cartStore.removeFromCart(index);
        },
        async handleCheckout() {
            this.loading = true;
            this.error = '';
            try {
                await this.cartStore.checkout();
                this.$router.push('/dashboard');
                // Optional: Show success toast
            } catch (err) {
                this.error = "Une erreur est survenue lors de la validation.";
            } finally {
                this.loading = false;
            }
        }
    }
};
</script>
