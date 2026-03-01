<template>
  <div class="min-h-screen bg-gray-100">
    <nav class="bg-white shadow">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="flex-shrink-0 flex items-center font-bold text-xl text-indigo-600">
              Casting App
            </div>
            <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                <router-link to="/dashboard" class="border-indigo-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                    Dashboard
                </router-link>
                 <router-link to="/catalog" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                    Catalogue
                </router-link>
            </div>
          </div>
          <div class="flex items-center gap-4">
            <span class="text-gray-700 hidden sm:inline" v-if="user">Bonjour, {{ user.email }}</span>
            <Button icon="pi pi-shopping-cart" label="Panier" class="p-button-text font-medium" :badge="cartStore.totalItems > 0 ? cartStore.totalItems.toString() : null" @click="$router.push('/cart')" />
            <Button label="Déconnexion" icon="pi pi-sign-out" class="p-button-text" @click="handleLogout" />
          </div>
        </div>
      </div>
    </nav>

    <div class="py-10">
      <header>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h1 class="text-3xl font-bold leading-tight text-gray-900">
            Mes Commandes
          </h1>
        </div>
      </header>
      <main>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
          <div class="px-4 py-6 sm:px-0">
             <div class="card">
                <DataTable :value="orders" :loading="isLoading" tableStyle="min-width: 50rem" removableSort>
                    <Column field="reference" header="Référence" sortable></Column>
                    <Column field="professional.user.email" header="Professionnel" sortable>
                        <template #body="slotProps">
                             {{ slotProps.data.professional?.firstName }} {{ slotProps.data.professional?.lastName }}
                        </template>
                    </Column>
                    <Column field="status" header="Statut" sortable>
                        <template #body="slotProps">
                            <Tag :value="slotProps.data.status" :severity="getSeverity(slotProps.data.status)" />
                        </template>
                    </Column>
                    <Column field="totalAmountTtc" header="Total TTC" sortable>
                        <template #body="slotProps">
                            {{ formatCurrency(slotProps.data.totalAmountTtc) }}
                        </template>
                    </Column>
                    <Column field="createdAt" header="Date" sortable>
                         <template #body="slotProps">
                            {{ new Date(slotProps.data.createdAt).toLocaleDateString() }}
                        </template>
                    </Column>
                </DataTable>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth';
import { useOrderStore } from '@/stores/order';
import { useCartStore } from '@/stores/cart';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';

export default {
    components: {
        Button,
        DataTable,
        Column,
        Tag
    },
    data() {
        return {
            cartStore: useCartStore(),
        };
    },
    computed: {
        user() {
            return useAuthStore().user;
        },
        orders() {
            return useOrderStore().orders;
        },
        isLoading() {
            return useOrderStore().loading;
        }
    },
    async mounted() {
        useOrderStore().fetchOrders();
        await this.cartStore.fetchCart();
    },
    methods: {
        handleLogout() {
            useAuthStore().logout();
            this.$router.push('/login');
        },
        getSeverity(status) {
            switch (status) {
                case 'COMPLETED':
                    return 'success';
                case 'IN_PROGRESS':
                    return 'info';
                case 'PAID_PENDING_PRO':
                    return 'warning';
                case 'PENDING_PAYMENT':
                    return 'danger';
                default:
                    return null;
            }
        },
        formatCurrency(value) {
            return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value / 100);
        }
    }
};
</script>
