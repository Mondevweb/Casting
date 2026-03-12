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
                <router-link to="/dashboard" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium" active-class="border-indigo-500 text-gray-900 border-b-2">
                    Dashboard
                </router-link>
                 <router-link to="/dashboard/mediatheque" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium" active-class="border-indigo-500 text-gray-900 border-b-2">
                    Ma Médiathèque
                </router-link>
                 <router-link to="/catalog" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium" active-class="border-indigo-500 text-gray-900 border-b-2">
                    Catalogue
                </router-link>
            </div>
          </div>
          <div class="flex items-center gap-4">
             <Button icon="pi pi-arrow-left" label="Retour" class="p-button-text font-medium" @click="$router.push('/dashboard')" />
          </div>
        </div>
      </div>
    </nav>

    <div class="py-10">
      <header>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h1 class="text-3xl font-bold leading-tight text-gray-900">
            Ma Médiathèque
          </h1>
          <p class="text-sm text-gray-500 mt-2">
            Gérez ici vos documents, photos et vidéos. Ils sont classés par type de prestation pour vous simplifier la vie lors de vos prochaines commandes.
          </p>
        </div>
      </header>
      
      <main>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
            
          <div v-if="libraryStore.loading && libraryStore.serviceTypes.length === 0" class="flex justify-center mt-20">
              <i class="pi pi-spin pi-spinner text-4xl text-indigo-600"></i>
          </div>

          <div v-else class="bg-white shadow rounded-lg p-6">
              
              <Tabs v-model:value="activeTab">
                  <TabList>
                      <Tab v-for="service in libraryStore.serviceTypes" :key="service.id" :value="service.id.toString()">
                          {{ service.name }}
                      </Tab>
                  </TabList>
                  
                  <TabPanels>
                      <TabPanel v-for="service in libraryStore.serviceTypes" :key="service.id" :value="service.id.toString()">
                          
                          <!-- Uploader section -->
                          <div class="mb-8 relative">
                              <h3 class="text-lg font-medium text-gray-900 mb-4">Ajouter un document pour : {{ service.name }}</h3>
                                <!-- Tuile Bouton d'Upload -->
                                <div 
                                    class="relative w-full h-32 rounded-lg shadow-sm border-2 border-dashed border-indigo-300 bg-indigo-50 hover:bg-indigo-100 flex flex-col items-center justify-center cursor-pointer transition-colors text-indigo-600 group overflow-hidden" 
                                    @click="triggerUpload(service.id)"
                                >
                                    <template v-if="uploadInProgress[service.id]">
                                        <div class="absolute bottom-0 left-0 h-full bg-indigo-200 transition-all duration-300 ease-out z-0" :style="{ width: (lastUploadProgress[service.id] || 0) + '%' }"></div>
                                        <div class="z-10 flex flex-col items-center">
                                            <i class="pi pi-cloud-upload text-4xl mb-2 animate-pulse text-indigo-700"></i>
                                            <span class="text-xs font-bold text-center leading-tight px-1 uppercase text-indigo-800">UPLOADING... {{ lastUploadProgress[service.id] || 0 }}%</span>
                                        </div>
                                    </template>
                                    <template v-else>
                                        <i class="pi pi-cloud-upload text-4xl mb-2 group-hover:scale-110 transition-transform z-10 text-indigo-400"></i>
                                        <span class="text-sm font-semibold text-center leading-tight px-1 text-indigo-700 z-10">Parcourir ou Glisser-Déposer</span>
                                        <span class="text-xs text-indigo-500 z-10 mt-1">Formats : {{ service.htmlAcceptMask }}</span>
                                    </template>
                                </div>
                              <div class="hidden">
                                <FileUpload
                                  :ref="'fileUpload_' + service.id"
                                  mode="basic"
                                  name="file[]"
                                  :url="uploadUrl"
                                  :multiple="true"
                                  :accept="service.htmlAcceptMask"
                                  :maxFileSize="50000000"
                                  :auto="true"
                                  customUpload
                                  @uploader="(e) => handleCustomUpload(e, service.id)"
                                  :showUploadButton="false"
                                  :showCancelButton="false"
                                  class="custom-fileupload"
                                />
                              </div>
                          </div>

                          <Divider />

                          <!-- Media Grid -->
                          <h3 class="text-lg font-medium text-gray-900 mb-4 mt-6">Vos documents enregistrés</h3>
                          
                          <div v-if="getMediasForService(service.id).length === 0" class="text-center py-10 bg-gray-50 rounded-lg border border-gray-100">
                              <i class="pi pi-folder-open text-gray-400 text-4xl mb-3"></i>
                              <p class="text-gray-500">Aucun document dans cette rubrique.</p>
                          </div>

                          <div v-else class="flex flex-wrap gap-4">
                              <div 
                                  v-for="media in getMediasForService(service.id)" 
                                  :key="media.id"
                                  class="relative group w-32 h-32 rounded-lg border border-gray-200 overflow-hidden bg-gray-50 flex items-center justify-center shadow-sm"
                              >
                                  <!-- Bouton suppression (Soft Delete) -->
                                  <button 
                                      @click.stop="confirmDelete(media)"
                                      class="absolute top-1 right-1 z-20 w-5 h-5 bg-white/90 hover:bg-red-50 hover:text-red-500 text-gray-600 rounded-full flex items-center justify-center shadow-sm opacity-0 group-hover:opacity-100 transition-all focus:outline-none"
                                      title="Supprimer définitivement de la médiathèque"
                                  >
                                      <i class="pi pi-times text-[10px] font-bold"></i>
                                  </button>
                                  
                                  <!-- Spinner de traitement (si vidéo en cours) -->
                                  <div v-if="media.transcodingStatus === 'PENDING' || media.transcodingStatus === 'PROCESSING'"
                                        class="absolute inset-0 bg-gray-900/40 z-10 flex flex-col items-center justify-center backdrop-blur-sm"
                                  >
                                      <i class="pi pi-spin pi-spinner text-white text-2xl mb-2"></i>
                                      <span class="text-white text-[10px] font-bold text-center leading-tight">TRAITEMENT<br>VIDÉO...</span>
                                  </div>

                                  <!-- Miniature selon le type de fichier -->
                                  <div class="w-full h-full flex items-center justify-center cursor-pointer group-hover:opacity-90 transition-opacity" @click="openGallery(media, service.id)">
                                      <img 
                                          v-if="media.thumbnailPath" 
                                          :src="baseApiUrl + '/uploads/media/' + media.thumbnailPath" 
                                          class="object-contain w-full h-full"
                                          alt="Thumbnail"
                                      />
                                      <img 
                                          v-else-if="isImage(media.originalName || media.filePath)" 
                                          :src="baseApiUrl + '/uploads/media/' + media.filePath" 
                                          class="object-contain w-full h-full"
                                          alt="Image"
                                      />
                                      <video 
                                          v-else-if="isVideo(media.originalName || media.filePath)" 
                                          :src="baseApiUrl + '/uploads/media/' + (media.webFilePath || media.filePath) + '#t=0.5'" 
                                          class="object-contain w-full h-full bg-black/5"
                                          preload="metadata"
                                          muted
                                          playsinline
                                      ></video>
                                      <i v-else-if="isAudio(media.originalName || media.filePath)" class="pi pi-headphones text-orange-500 text-3xl"></i>
                                      <PdfThumbnail 
                                          v-else-if="isPdf(media.originalName || media.filePath)" 
                                          :src="'/uploads/media/' + media.filePath" 
                                      />
                                      <i v-else class="pi pi-file text-gray-500 text-3xl"></i>
                                      
                                      <!-- Zoom icon overlay -->
                                      <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity pointer-events-none rounded-lg">
                                          <i class="pi pi-search-plus text-white text-2xl font-bold"></i>
                                      </div>
                                  </div>
                              </div>
                          </div>

                      </TabPanel>
                  </TabPanels>
              </Tabs>
          </div>
        </div>
      </main>
      
      <ConfirmDialog :style="{ width: '400px', maxWidth: '90vw' }"></ConfirmDialog>

      <!-- Media Gallery -->
      <MediaGallery
          v-model:visible="isGalleryVisible"
          :medias="currentGalleryMedias"
          :initialIndex="currentMediaIndex"
      />
    </div>
  </div>
</template>

<script>
import { useMediaLibraryStore } from '@/stores/mediaLibrary';
import { useAuthStore } from '@/stores/auth';
import Button from 'primevue/button';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import FileUpload from 'primevue/fileupload';
import Divider from 'primevue/divider';
import ConfirmDialog from 'primevue/confirmdialog';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import MediaGallery from '../../components/MediaGallery.vue';
import PdfThumbnail from '../../components/PdfThumbnail.vue';
import { uploadVideoToBunny } from '@/services/bunnyUploadService';
import axios from 'axios';

export default {
    components: {
        Button, Tabs, TabList, Tab, TabPanels, TabPanel, FileUpload, Divider, ConfirmDialog, MediaGallery, PdfThumbnail
    },
    setup() {
        const toast = useToast();
        const confirm = useConfirm();
        return { toast, confirm };
    },
    data() {
        return {
            libraryStore: useMediaLibraryStore(),
            activeTab: "1",
            baseApiUrl: import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000',
            uploadUrl: (import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000') + '/api/media_objects',
            pollingInterval: null,
            uploadInProgress: {},
            lastUploadProgress: {},
            isGalleryVisible: false,
            currentGalleryMedias: [],
            currentMediaIndex: 0
        };
    },
    computed: {
        user() {
            return useAuthStore().user;
        }
    },
    async mounted() {
        await this.libraryStore.fetchServiceTypes();
        if (this.libraryStore.serviceTypes.length > 0) {
            this.activeTab = this.libraryStore.serviceTypes[0].id.toString();
        }
        await this.libraryStore.fetchLibrary();
        
        // Polling pour surveiller la fin du transcodage vidéo si on reste sur la page
        this.startPolling();
    },
    unmounted() {
        this.stopPolling();
    },
    methods: {
        getMediasForService(serviceId) {
            return this.libraryStore.getMediaByServiceType(serviceId);
        },
        async handleCustomUpload(event, serviceTypeId) {
            const files = event.files;
            const authStore = useAuthStore();
            const token = authStore.token;

            this.uploadInProgress[serviceTypeId] = true;
            this.lastUploadProgress[serviceTypeId] = 0;

            for (let file of files) {
                try {
                    if (this.isVideo(file.name)) {
                        // Bunny Stream Upload pour les vidéos
                        await uploadVideoToBunny(file, serviceTypeId, (progress) => {
                            this.lastUploadProgress[serviceTypeId] = progress;
                        });
                    } else {
                        // API Platform PHP Upload standard pour les images/PDF
                        const formData = new FormData();
                        formData.append('file', file);
                        formData.append('serviceType', `/api/service_types/${serviceTypeId}`);

                        await axios.post(this.uploadUrl, formData, {
                            headers: {
                                'Authorization': `Bearer ${token}`,
                                'Accept': 'application/ld+json',
                                'Content-Type': 'multipart/form-data'
                            },
                            onUploadProgress: (progressEvent) => {
                                const progress = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                                this.lastUploadProgress[serviceTypeId] = progress;
                            }
                        });
                    }
                } catch (err) {
                    console.error("Erreur d'upload:", err);
                    this.onUploadError(err, serviceTypeId);
                    return;
                }
            }

            // Tout est terminé
            event.options.clear();
            await this.onUploadComplete(null, serviceTypeId);
        },
        async onUploadComplete(event, serviceTypeId) {
            this.toast.add({ severity: 'success', summary: 'Succès', detail: 'Fichier(s) ajouté(s) à votre médiathèque', life: 3000 });
            
            // Clear PrimeVue FileUpload cache
            const refName = 'fileUpload_' + serviceTypeId;
            if (this.$refs[refName]) {
                const uploaderInstance = Array.isArray(this.$refs[refName]) ? this.$refs[refName][0] : this.$refs[refName];
                if (uploaderInstance) {
                    if (typeof uploaderInstance.clear === 'function') uploaderInstance.clear();
                    if (uploaderInstance.uploadedFiles) uploaderInstance.uploadedFiles = [];
                }
            }
            
            this.uploadInProgress[serviceTypeId] = false;
            await this.libraryStore.fetchLibrary();
        },
        onUploadError(event, serviceTypeId) {
            this.toast.add({ severity: 'error', summary: 'Erreur', detail: 'L\'upload a échoué', life: 3000 });
            this.uploadInProgress[serviceTypeId] = false;
        },
        triggerUpload(serviceId) {
            const refName = 'fileUpload_' + serviceId;
            if (this.$refs[refName] && this.$refs[refName][0]) {
                const fileInput = this.$refs[refName][0].$el.querySelector('input[type="file"]');
                if (fileInput) fileInput.click();
            }
        },
        openGallery(media, serviceId) {
            this.currentGalleryMedias = this.getMediasForService(serviceId);
            const index = this.currentGalleryMedias.findIndex(m => m.id === media.id);
            this.currentMediaIndex = index !== -1 ? index : 0;
            this.isGalleryVisible = true;
        },
        confirmDelete(media) {
            this.confirm.require({
                message: 'Voulez-vous vraiment supprimer ce document de votre médiathèque ? Il restera accessible pour les professionnels si vous l\'avez déjà assigné à une commande.',
                header: 'Confirmation de suppression',
                icon: 'pi pi-exclamation-triangle',
                acceptLabel: 'Oui, supprimer',
                rejectLabel: 'Annuler',
                acceptClass: 'p-button-danger',
                accept: async () => {
                    try {
                        await this.libraryStore.deleteMedia(media.id);
                        this.toast.add({ severity: 'success', summary: 'Succès', detail: 'Média supprimé', life: 3000 });
                    } catch (e) {
                        this.toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de supprimer ce média', life: 3000 });
                    }
                }
            });
        },
        
        // Helpers (Same as OrderConfigModal)
        isImage(filename) {
            if (!filename) return false;
            return /\.(jpg|jpeg|png|gif|webp)$/i.test(filename);
        },
        isVideo(filename) {
            if (!filename) return false;
            return /\.(mp4|mov|avi|wmv|flv|mkv|webm)$/i.test(filename);
        },
        isAudio(filename) {
            if (!filename) return false;
            return /\.(mp3|wav|ogg|aac|flac)$/i.test(filename);
        },
        isPdf(filename) {
            if (!filename) return false;
            return /\.pdf$/i.test(filename);
        },
        
        // Polling video transcoding
        startPolling() {
            this.pollingInterval = setInterval(async () => {
                const pendingVideos = this.libraryStore.medias.filter(m => m.transcodingStatus === 'PENDING' || m.transcodingStatus === 'PROCESSING');
                if (pendingVideos.length > 0) {
                    // Fallback local: forcer le backend à vérifier l'état chez Bunny CDN
                    const authStore = useAuthStore();
                    for (const video of pendingVideos) {
                        if (video.bunnyVideoId) {
                            try {
                                await axios.get(`${this.baseApiUrl}/api/bunny-stream/check-video/${video.bunnyVideoId}`, {
                                    headers: { 'Authorization': `Bearer ${authStore.token}`, 'Accept': 'application/json' }
                                });
                            } catch (e) {
                                console.error("Erreur check-video fallback", e);
                            }
                        }
                    }
                    await this.libraryStore.fetchLibrary();
                }
            }, 5000);
        },
        stopPolling() {
            if (this.pollingInterval) clearInterval(this.pollingInterval);
        }
    }
};
</script>

<style>
/* Nettoyage visuel brut pour cacher PrimeVue FileUpload interne */
.custom-fileupload.p-fileupload {
    border: none !important;
    background: transparent !important;
    padding: 0 !important;
    margin: 0 !important;
}
.custom-fileupload .p-fileupload-buttonbar,
.custom-fileupload .p-fileupload-content {
    display: none !important;
    border: none !important;
    background: transparent !important;
    padding: 0 !important;
    margin: 0 !important;
}
.custom-fileupload .p-fileupload-content > span.hidden {
    display: none !important;
}
.custom-fileupload .p-button.p-fileupload-choose {
    display: none !important;
}
</style>
