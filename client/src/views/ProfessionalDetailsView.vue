<template>
  <div class="min-h-screen bg-gray-50 pb-20">
    <!-- Navigation -->
    <nav class="bg-white/80 backdrop-blur-md sticky top-0 z-50 border-b border-gray-100">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
           <div class="flex items-center gap-4">
             <Button icon="pi pi-arrow-left" class="p-button-text p-button-rounded text-gray-600 hover:bg-gray-100" @click="$router.push('/catalog')" v-tooltip.bottom="'Retour au catalogue'" />
             <div class="font-semibold text-lg text-gray-900 tracking-tight">
               Détail du Professionnel
             </div>
           </div>
           <Button icon="pi pi-shopping-cart" label="Panier" class="p-button-text font-medium" :badge="cartStore.totalItems > 0 ? cartStore.totalItems.toString() : null" @click="$router.push('/cart')" />
        </div>
      </div>
    </nav>

    <div v-if="loading" class="flex justify-center items-center h-[calc(100vh-64px)]">
        <i class="pi pi-spin pi-spinner text-indigo-600 text-4xl"></i>
    </div>

    <div v-else-if="professional" class="animate-fade-in-up">
        <!-- Hero Section -->
        <div class="relative bg-indigo-600 h-64 sm:h-80">
            <div class="absolute inset-0 bg-gradient-to-r from-indigo-800 to-indigo-600 opacity-90"></div>
            <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10"></div>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-full flex items-end pb-12 sm:pb-16 relative z-10">
                <!-- Content handled in overlap section below -->
            </div>
        </div>

        <!-- Main Content with Overlap -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-24 sm:-mt-32 relative z-20">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Left Column: Profile Card -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-2xl shadow-xl overflow-hidden sticky top-24">
                        <div class="p-6 sm:p-8 flex flex-col items-center text-center">
                            <div class="relative mb-4">
                                <img class="h-32 w-32 sm:h-40 sm:w-40 rounded-full object-cover border-4 border-white shadow-lg" 
                                     :src="`https://ui-avatars.com/api/?name=${professional.firstName}+${professional.lastName}&background=random&size=256`" 
                                     :alt="professional.firstName" />
                                <div class="absolute bottom-2 right-2 bg-green-500 rounded-full p-1.5 border-2 border-white" title="Vérifié">
                                    <i class="pi pi-check text-white text-xs font-bold"></i>
                                </div>
                            </div>
                            
                            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ professional.firstName }} {{ professional.lastName }}</h1>
                            <p class="text-indigo-600 font-medium text-lg mt-1">{{ professional.jobTitle?.name }}</p>
                            
                            <div class="flex items-center gap-2 text-gray-500 mt-3 text-sm">
                                <i class="pi pi-map-marker text-indigo-400"></i>
                                <span>{{ professional.city }} ({{ professional.departmentCode }})</span>
                            </div>

                            <div class="mt-6 flex flex-wrap justify-center gap-2 w-full">
                                <Tag v-for="spec in professional.specialties" :key="spec.id" :value="spec.name" severity="secondary" rounded class="px-3 py-1"></Tag>
                            </div>
                        </div>
                        
                        <div class="border-t border-gray-100 p-6 bg-gray-50">
                            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-3">À propos</h3>
                            <p class="text-gray-600 text-sm leading-relaxed">{{ professional.biography || "Ce professionnel n'a pas encore ajouté de biographie." }}</p>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Services -->
                <div class="lg:col-span-2">
                     <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                        <i class="pi pi-briefcase text-indigo-500"></i>
                        Prestations disponibles
                    </h2>

                    <div v-if="professional.proServices && professional.proServices.length > 0" class="grid grid-cols-1 gap-6">
                        <div v-for="service in professional.proServices" :key="service.id" 
                             class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sm:p-8 hover:shadow-md transition-shadow duration-300 flex flex-col sm:flex-row justify-between gap-6 group relative overflow-hidden">
                             
                            <div class="absolute top-0 left-0 w-1 h-full bg-indigo-500 opacity-0 group-hover:opacity-100 transition-opacity"></div>

                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="text-xl font-bold text-gray-900 group-hover:text-indigo-600 transition-colors">{{ service.serviceType?.name }}</h3>
                                </div>
                                <p class="text-gray-600 mb-4 leading-relaxed">{{ service.serviceType?.description }}</p>
                                
                                <div class="flex items-center gap-2 text-sm text-gray-500">
                                    <i class="pi pi-clock"></i>
                                    <span>Délai moyen : {{ professional.standardDelayDays || 3 }} jours</span>
                                </div>
                            </div>

                            <div class="flex flex-col items-start sm:items-end gap-4 min-w-[140px]">
                                <div class="text-right flex flex-col items-end">
                                    <span class="text-3xl font-extrabold text-gray-900 leading-none">{{ formatCurrency(service.basePrice) }}</span>
                                    
                                    <div v-if="service.serviceType?.discriminator === 'unit'" class="text-xs text-gray-500 mt-1 text-right">
                                        pour {{ service.serviceType.baseQuantity }} {{ service.serviceType.unitName }}{{ service.serviceType.baseQuantity > 1 ? 's' : '' }}
                                        <div v-if="service.supplementPrice > 0" class="text-indigo-600 font-semibold mt-0.5">
                                            + {{ formatCurrency(service.supplementPrice) }} / supp.
                                        </div>
                                    </div>
                                    
                                    <div v-else-if="service.serviceType?.discriminator === 'duration'" class="text-xs text-gray-500 mt-1 text-right">
                                        pour {{ service.serviceType.baseDurationMin }} min
                                        <div v-if="service.supplementPrice > 0" class="text-indigo-600 font-semibold mt-0.5">
                                            + {{ formatCurrency(service.supplementPrice) }} / {{ service.serviceType.durationStep }} min supp.
                                        </div>
                                    </div>
                                </div>
                                <Button 
                                    v-if="!getExistingCartItem(service.id)"
                                    label="Configurer" 
                                    icon="pi pi-cog" 
                                    class="w-full sm:w-auto p-button-rounded" 
                                    @click="openOrderModal(service)"
                                />
                                <Button 
                                    v-else
                                    label="Modifier" 
                                    icon="pi pi-pencil" 
                                    class="w-full sm:w-auto p-button-rounded p-button-secondary p-button-outlined" 
                                    @click="openOrderModal(service, getExistingCartItem(service.id))"
                                    v-tooltip.top="'Ce service est déjà dans votre panier'"
                                />
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-12 bg-white rounded-xl border border-dashed border-gray-300">
                        <i class="pi pi-inbox text-4xl text-gray-300 mb-3 block"></i>
                        <p class="text-gray-500">Aucune prestation disponible pour le moment.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div v-else class="flex flex-col items-center justify-center min-h-[50vh] text-center">
        <i class="pi pi-exclamation-circle text-4xl text-gray-400 mb-4"></i>
        <h2 class="text-xl font-semibold text-gray-900">Professionnel introuvable</h2>
        <p class="text-gray-500 mt-2">Ce profil n'existe pas ou a été supprimé.</p>
        <Button label="Retour au catalogue" class="mt-6 p-button-outlined" @click="$router.push('/catalog')" />
    </div>

    <!-- Modale de configuration de commande -->
    <OrderConfigModal 
        v-if="selectedService"
        :visible="isModalVisible" 
        :service="selectedService" 
        :professional="professional"
        :existingItem="existingCartItem"
        @update:visible="isModalVisible = $event"
        @added="handleServiceAdded" />
  </div>
</template>

<script>
import { useProfessionalStore } from '@/stores/professional';
import { useCartStore } from '@/stores/cart';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import OrderConfigModal from '@/components/OrderConfigModal.vue';

export default {
    components: {
        Button,
        Tag,
        OrderConfigModal
    },
    data() {
        return {
            proStore: useProfessionalStore(),
            cartStore: useCartStore(),
            isModalVisible: false,
            selectedService: null,
            existingCartItem: null
        };
    },
    computed: {
        professional() {
            const pro = this.proStore.currentProfessional;
            if (pro && pro.proServices) {
                return {
                    ...pro,
                    proServices: pro.proServices.filter(service => service.isActive)
                };
            }
            return pro;
        },
        loading() {
            return this.proStore.loading;
        }
    },
    async mounted() {
        const id = this.$route.params.id;
        if (id) {
            this.proStore.fetchProfessional(id);
        }
        await this.cartStore.fetchCart(); // Pré-charge le panier pour le badge
    },
    methods: {
        formatCurrency(value) {
            return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value / 100);
        },
        getExistingCartItem(serviceId) {
            if (!this.cartStore.allItems) return null;
            return this.cartStore.allItems.find(item => 
                (item.service === `/api/pro_services/${serviceId}` || 
                 (item.service && item.service['@id'] === `/api/pro_services/${serviceId}`) ||
                 (item.service && item.service.id === serviceId)) &&
                // S'assurer qu'il s'agit bien de la commande DU professionnel actuel
                (item.professional && (item.professional.id === this.professional.id || item.professional['@id'] === `/api/professionals/${this.professional.id}`))
            );
        },
        openOrderModal(service, existingItem = null) {
            this.selectedService = service;
            this.existingCartItem = existingItem;
            this.isModalVisible = true;
        },
        handleServiceAdded() {
            this.isModalVisible = false;
            // Optionnel : Toast ici pour dire "Ajouté avec succès !"
            this.selectedService = null;
        }
    }
};
</script>
