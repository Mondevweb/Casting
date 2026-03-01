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
        <div v-if="cartStore.loading && totalItems === 0" class="flex justify-center mt-20">
            <i class="pi pi-spin pi-spinner text-4xl text-indigo-600"></i>
        </div>

        <div v-else-if="cartOrders && cartOrders.length > 0" class="flex flex-col lg:flex-row gap-6">
            <!-- List Items by Professional -->
            <div class="flex-1 flex flex-col gap-6">
                <div v-for="order in cartStore.orders" :key="order.id" class="bg-white shadow rounded-xl p-6 border border-gray-100">
                    <!-- Professional Header -->
                    <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-4">
                        <div class="flex items-center gap-3">
                            <Avatar :image="`https://ui-avatars.com/api/?name=${order.professional?.firstName}+${order.professional?.lastName}&background=random`" shape="circle" size="large" />
                            <div>
                                <h2 class="text-lg font-bold text-gray-900 leading-tight">
                                    <router-link :to="`/professional/${order.professional?.id}`" class="hover:text-indigo-600 transition-colors">
                                        {{ order.professional?.firstName }} {{ order.professional?.lastName }}
                                    </router-link>
                                </h2>
                                <p class="text-xs text-gray-500">Commande provisoire #{{ order.reference }}</p>
                            </div>
                        </div>
                        <Button icon="pi pi-chevron-right" class="p-button-rounded p-button-text p-button-sm" v-tooltip="'Voir le profil'" @click="$router.push(`/professional/${order.professional?.id}`)" />
                    </div>

                    <!-- Order Lines -->
                    <div class="flex flex-col gap-2">
                        <div v-for="item in order.orderLines" :key="item.id" class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 p-4 hover:bg-gray-50 rounded-lg transition-colors border border-transparent hover:border-gray-200">
                            <div>
                                 <h3 class="text-md font-semibold text-gray-800">
                                    {{ item.serviceType?.name || 'Prestation' }}
                                 </h3>
                                 <p class="text-sm text-gray-500 mb-1" v-if="item.mediaObjects && item.mediaObjects.length > 0">
                                    <i class="pi pi-paperclip text-xs mr-1"></i> {{ item.mediaObjects.length }} média(s) joint(s)
                                 </p>
                                 <p class="text-sm text-gray-500" v-if="item.instructions">
                                    <i class="pi pi-align-left text-xs mr-1"></i> Instructions incluses
                                 </p>
                                 <p class="text-sm text-gray-500 mt-1">
                                    Quantité : <span class="font-medium text-gray-700">{{ item.quantityBilled }}</span>
                                 </p>
                            </div>
                            <div class="flex items-center gap-4 w-full sm:w-auto justify-between sm:justify-end">
                                 <div class="text-right">
                                     <div class="font-bold text-lg text-indigo-600">{{ formatCurrency(item.lineTotalAmount) }}</div>
                                     <div class="text-xs text-gray-400" v-if="item.quantityBilled > 1 || item.lineTotalAmount > item.basePriceFrozen">{{ formatCurrency(item.basePriceFrozen) }} de base</div>
                                </div>
                                <div class="flex items-center gap-1">
                                    <Button icon="pi pi-pencil" class="p-button-text p-button-rounded text-gray-500 hover:text-indigo-600" title="Modifier la prestation" @click="openEditModal(item, order.professional)" />
                                    <Button icon="pi pi-trash" class="p-button-danger p-button-text p-button-rounded" title="Retirer" @click="removeItem(item.id)" :loading="isRemoving === item.id" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Sidebar -->
            <div class="w-full lg:w-80">
                <div class="bg-white shadow rounded-lg p-6 sticky top-6">
                    <h2 class="text-xl font-bold mb-4 border-b pb-2">Récapitulatif</h2>
                    <div class="flex justify-between mb-2 text-gray-600">
                        <span>Articles ({{ totalItems }})</span>
                        <span>{{ formatCurrency(totalAmount) }}</span>
                    </div>
                    <hr class="my-4 border-gray-100" />
                    <div class="flex justify-between mb-6 text-xl font-bold text-gray-900">
                        <span>Total TTC</span>
                        <span>{{ formatCurrency(totalAmount) }}</span>
                    </div>
                    
                    <Button label="Valider la commande" icon="pi pi-check" class="w-full p-button-lg shadow-md" :loading="isCheckingOut" :disabled="isCheckingOut || isRemoving !== null || cartStore.loading" @click="handleCheckout" />
                    
                    <div v-if="error || cartStore.error" class="mt-4 p-3 bg-red-50 text-red-600 rounded-md text-sm border border-red-100 flex items-start gap-2">
                        <i class="pi pi-exclamation-triangle mt-0.5"></i>
                        <span>{{ error || cartStore.error }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="text-center mt-32 bg-white rounded-2xl shadow-sm p-12 max-w-2xl mx-auto border border-gray-50">
            <div class="bg-indigo-50 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="pi pi-shopping-cart text-5xl text-indigo-300"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Votre panier est tristement vide</h2>
            <p class="text-gray-500 text-lg mb-8 max-w-md mx-auto">Explorez notre catalogue de talents et trouvez les professionnels qui donneront vie à votre projet.</p>
            <Button label="Parcourir le catalogue" icon="pi pi-search" size="large" @click="$router.push('/catalog')" />
        </div>
    </div>

    <!-- Modale d'édition des prestations existantes -->
    <OrderConfigModal 
        v-if="selectedServiceForEdit && selectedProfessionalForEdit"
        v-model:visible="isEditModalVisible" 
        :service="selectedServiceForEdit"
        :professional="selectedProfessionalForEdit"
        :existingItem="selectedItemForEdit"
        @added="isEditModalVisible = false"
    />
  </div>
</template>

<script>
import { useCartStore } from '@/stores/cart';
import Button from 'primevue/button';
import Avatar from 'primevue/avatar';
import { useToast } from 'primevue/usetoast';
import OrderConfigModal from '@/components/OrderConfigModal.vue';

export default {
    components: {
        Button,
        Avatar,
        OrderConfigModal
    },
    setup() {
        const toast = useToast();
        return { toast };
    },
    data() {
        return {
            cartStore: useCartStore(),
            isRemoving: null,
            isCheckingOut: false,
            error: '',
            
            // Modal Edit State
            isEditModalVisible: false,
            selectedServiceForEdit: null,
            selectedProfessionalForEdit: null,
            selectedItemForEdit: null
        };
    },
    computed: {
        cartOrders() {
            return this.cartStore.orders;
        },
        totalItems() {
            return this.cartStore.totalItems;
        },
        totalAmount() {
            return this.cartStore.totalAmount;
        }
    },
    async mounted() {
        // Au montage du composant complet, on fetch le panier côté serveur
        await this.cartStore.fetchCart();
    },
    methods: {
        formatCurrency(value) {
            if (!value) return '0,00 €';
            return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value / 100);
        },
        openEditModal(item, professional) {
            // Associer les données requises par OrderConfigModal
            this.selectedServiceForEdit = item.service;
            this.selectedProfessionalForEdit = professional;
            this.selectedItemForEdit = item;
            
            // Ouvrir la Sidebar
            this.isEditModalVisible = true;
        },
        async removeItem(id) {
            this.isRemoving = id;
            try {
                await this.cartStore.removeFromCart(id);
                this.toast.add({
                    severity: 'info', 
                    summary: 'Article retiré', 
                    detail: 'La prestation a été retirée de votre panier.', 
                    life: 3000 
                });
            } catch (e) {
                // error is already handled and stored in cartStore.error
                this.toast.add({
                    severity: 'error', 
                    summary: 'Erreur', 
                    detail: 'Impossible de retirer cet article.', 
                    life: 4000 
                });
            } finally {
                this.isRemoving = null;
            }
        },
        async handleCheckout() {
            this.isCheckingOut = true;
            this.error = '';
            try {
                await this.cartStore.checkout();
                this.toast.add({
                    severity: 'success', 
                    summary: 'Commande validée', 
                    detail: 'Votre commande a bien été validée et transmise au professionnel.', 
                    life: 5000 
                });
                // Redirection vers le dashboard, ou vers une page de paiement Stripe (à implémenter plus tard)
                this.$router.push('/dashboard');
            } catch (err) {
                this.error = "Une erreur est survenue lors de la validation. Veuillez réessayer.";
            } finally {
                this.isCheckingOut = false;
            }
        }
    }
};
</script>
