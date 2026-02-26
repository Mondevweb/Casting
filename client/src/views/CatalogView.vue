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
                <router-link to="/dashboard" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                    Dashboard
                </router-link>
                 <router-link to="/catalog" class="border-indigo-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                    Catalogue
                </router-link>
            </div>
          </div>
          <div class="flex items-center">
            <Button label="Déconnexion" icon="pi pi-sign-out" class="p-button-text" @click="handleLogout" />
          </div>
        </div>
      </div>
    </nav>

    <div class="py-10">
      <header>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h1 class="text-3xl font-bold leading-tight text-gray-900">
            Trouver un Pro
          </h1>
        </div>
      </header>
      <main>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
          <div class="px-4 py-6 sm:px-0">
             <div v-if="loading" class="flex justify-center my-10">
                <i class="pi pi-spin pi-spinner text-4xl text-indigo-600"></i>
             </div>
             <div v-else class="card">
                <DataView :value="professionals" :layout="layout">
                    <template #header>
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center bg-gray-100 p-2 rounded gap-4">
                            <div class="flex flex-wrap gap-2 w-full sm:w-auto flex-1">
                                <IconField iconPosition="left" class="w-full sm:w-64">
                                    <InputIcon class="pi pi-search" />
                                    <InputText v-model="searchQuery" placeholder="Rechercher (Nom, Ville...)" class="w-full" @input="onSearch" />
                                </IconField>

                                <MultiSelect v-model="selectedJobTitles" :options="jobTitles" optionLabel="name" placeholder="Métiers" 
                                    :maxSelectedLabels="2" class="w-full sm:w-48" @change="onJobTitleChange" />
                                
                                <MultiSelect v-model="selectedSpecialties" :options="specialties" optionLabel="name" placeholder="Spécialités" 
                                    :maxSelectedLabels="2" class="w-full sm:w-48" @change="onSpecialtyChange" />
                            </div>

                            <SelectButton v-model="layout" :options="layoutOptions" :allowEmpty="false">
                                <template #option="{ option }">
                                    <i :class="[option === 'list' ? 'pi pi-bars' : 'pi pi-th-large']"></i>
                                </template>
                            </SelectButton>
                        </div>
                    </template>

                    <template #list="slotProps">
                        <div class="grid grid-cols-12 gap-4">
                            <div v-for="(item, index) in slotProps.items" :key="index" class="col-span-12">
                                <div class="flex flex-col sm:flex-row sm:items-center p-6 gap-4 border-b border-gray-200 bg-white rounded-lg">
                                    <div class="md:w-40 relative">
                                        <img class="block xl:block mx-auto rounded-full w-full max-w-[100px]" :src="`https://ui-avatars.com/api/?name=${item.firstName}+${item.lastName}&background=random`" :alt="item.firstName" />
                                    </div>
                                    <div class="flex flex-col md:flex-row justify-between md:items-center flex-1 gap-6">
                                        <div class="flex flex-col justify-between items-start gap-2">
                                            <div>
                                                <span class="font-medium text-secondary text-sm">{{ item.jobTitle?.name }}</span>
                                                <div class="text-lg font-medium text-gray-900 mt-2">{{ item.firstName }} {{ item.lastName }}</div>
                                            </div>
                                            <div class="flex flex-col gap-1 text-sm text-gray-600">
                                                <span>📍 {{ item.city }} ({{ item.departmentCode }})</span>
                                                <div class="flex gap-2 mt-1">
                                                    <Tag v-for="spec in item.specialties" :key="spec.id" :value="spec.name" severity="info" class="text-xs"></Tag>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex flex-col md:items-end gap-5">
                                            <span class="text-xl font-bold text-gray-900">À partir de 50€</span>
                                            <div class="flex gap-2">
                                                <Button icon="pi pi-heart" outlined></Button>
                                                <Button icon="pi pi-eye" label="Voir services" :disabled="item.inventoryStatus === 'OUTOFSTOCK'" class="flex-auto md:flex-initial whitespace-nowrap" @click="$router.push(`/professional/${item.id}`)"></Button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template #grid="slotProps">
                        <div class="grid grid-cols-12 gap-4">
                            <div v-for="(item, index) in slotProps.items" :key="index" class="col-span-12 sm:col-span-6 md:col-span-4 xl:col-span-4 p-2">
                                <div class="p-6 border border-gray-200 bg-white rounded-lg flex flex-col gap-4 h-full">
                                    <div class="flex justify-center rounded p-4 bg-gray-50">
                                        <img class="w-24 h-24 rounded-full object-cover" :src="`https://ui-avatars.com/api/?name=${item.firstName}+${item.lastName}&background=random`" :alt="item.firstName" />
                                    </div>
                                    <div class="pt-4 flex flex-col flex-1 gap-4">
                                        <div class="flex flex-row justify-between items-start gap-2">
                                            <div>
                                                <span class="font-medium text-secondary text-sm">{{ item.jobTitle?.name }}</span>
                                                <div class="text-lg font-medium text-gray-900 mt-1">{{ item.firstName }} {{ item.lastName }}</div>
                                            </div>
                                            <span class="font-bold text-gray-900">50€</span>
                                        </div>
                                        <div class="flex flex-col gap-2">
                                            <span class="text-sm text-gray-500">📍 {{ item.city }}</span>
                                            <div class="flex flex-wrap gap-1">
                                                <Tag v-for="spec in item.specialties.slice(0,3)" :key="spec.id" :value="spec.name" severity="info" class="text-xs"></Tag>
                                            </div>
                                        </div>
                                        <div class="flex flex-col gap-4 mt-auto">
                                             <div class="flex gap-2">
                                                <Button icon="pi pi-eye" label="Voir services" class="flex-auto whitespace-nowrap" @click="$router.push(`/professional/${item.id}`)"></Button>
                                                <Button icon="pi pi-heart" outlined></Button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </DataView>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth';
import { useProfessionalStore } from '@/stores/professional';
import { useJobTitleStore } from '@/stores/jobTitle';
import { useSpecialtyStore } from '@/stores/specialty';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import DataView from 'primevue/dataview';
import SelectButton from 'primevue/selectbutton';
import Tag from 'primevue/tag';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import MultiSelect from 'primevue/multiselect';

export default {
    components: {
        Button,
        DataView,
        SelectButton,
        Tag,
        InputText,
        IconField,
        InputIcon,
        MultiSelect
    },
    data() {
        return {
            layout: 'grid',
            layoutOptions: ['list', 'grid'],
            professionalStore: useProfessionalStore(),
            jobTitleStore: useJobTitleStore(),
            specialtyStore: useSpecialtyStore(),
            searchQuery: useProfessionalStore().filters.search,
            selectedJobTitles: useProfessionalStore().filters.jobTitles,
            selectedSpecialties: useProfessionalStore().filters.specialties
        };
    },
    computed: {
        professionals() {
            return this.professionalStore.filteredProfessionals;
        },
        loading() {
            return this.professionalStore.loading;
        },
        jobTitles() {
            return this.jobTitleStore.jobTitles;
        },
        specialties() {
            return this.specialtyStore.specialties;
        }
    },
    mounted() {
        this.professionalStore.fetchProfessionals();
        this.jobTitleStore.fetchJobTitles();
        this.specialtyStore.fetchSpecialties();
    },
    methods: {
        handleLogout() {
            useAuthStore().logout();
            this.$router.push('/login');
        },
        onSearch() {
            this.professionalStore.updateSearch(this.searchQuery);
        },
        onJobTitleChange() {
            this.professionalStore.updateJobTitles(this.selectedJobTitles);
        },
        onSpecialtyChange() {
            this.professionalStore.updateSpecialties(this.selectedSpecialties);
        }
    }
};
</script>
