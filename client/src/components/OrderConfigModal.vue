<template>
    <Sidebar
        :visible="visible"
        position="right"
        :blockScroll="false"
        :header="
            existingItem
                ? 'Modification de la prestation'
                : 'Configuration de la commande'
        "
        class="order-sidebar p-0"
        @update:visible="$emit('update:visible', $event)"
    >
        <div v-if="service" class="flex flex-col h-full bg-white relative">
            <!-- Zone scrollable -->
            <div
                class="flex-grow overflow-y-auto px-6 py-4 flex flex-col gap-6"
            >
                <!-- Info du service -->
                <div
                    class="bg-indigo-50 p-4 rounded-lg flex justify-between items-start border border-indigo-100"
                >
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-indigo-900 text-lg">
                                {{ service.serviceType?.name }}
                            </h3>
                            <Button 
                                :icon="showDetails ? 'pi pi-chevron-up' : 'pi pi-info-circle'" 
                                class="p-button-rounded p-button-text p-button-sm p-button-secondary !w-8 !h-8" 
                                @click="showDetails = !showDetails"
                                v-tooltip.top="'Voir les détails et instructions'"
                            />
                        </div>
                        
                        <transition name="fade-slide">
                            <p v-if="showDetails && service.serviceType?.description" class="text-xs text-gray-600 mt-2 mb-2 max-w-sm bg-indigo-50/50 p-2 rounded border border-indigo-100/50">
                                {{ service.serviceType.description }}
                            </p>
                        </transition>
                        
                        <p class="text-sm text-indigo-700 mt-1 flex items-center gap-1.5">
                            <i class="pi pi-user text-xs"></i>
                            Avec {{ professional?.firstName }} {{ professional?.lastName }}
                        </p>
                    </div>
                    <div class="text-right flex flex-col items-end">
                        <span class="font-bold text-indigo-900 text-lg leading-none">{{ formatCurrency(service.basePrice) }}</span>
                        
                        <!-- Variante de prix: Unité (Photos, Pages...) -->
                        <div v-if="service.serviceType?.discriminator === 'unit'" class="text-xs text-indigo-600 mt-1 text-right">
                            <span class="opacity-80">
                                Inclus : {{ service.serviceType.baseQuantity }} {{ service.serviceType.unitName }}{{ service.serviceType.baseQuantity > 1 ? 's' : '' }}
                            </span>
                            <div v-if="service.supplementPrice > 0" class="font-semibold">
                                puis {{ formatCurrency(service.supplementPrice) }} / supp.
                            </div>
                        </div>
                        
                        <!-- Variante de prix: Durée (Audiodescription, Vidéos...) -->
                        <div v-else-if="service.serviceType?.discriminator === 'duration'" class="text-xs text-indigo-600 mt-1 text-right">
                            <span class="opacity-80">
                                Inclus : {{ service.serviceType.baseDurationMin }} min
                            </span>
                            <div v-if="service.supplementPrice > 0" class="font-semibold">
                                puis {{ formatCurrency(service.supplementPrice) }} / minute supp.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Instructions optionnelles en haut pour meilleure UX -->
                <div>
                    <transition name="fade-slide">
                        <div v-if="showDetails && service.serviceType?.instructionsHelp" class="text-sm text-indigo-800 bg-indigo-100/70 p-3 rounded-md mb-3 border border-indigo-200 flex items-start gap-2 shadow-sm">
                            <i class="pi pi-info-circle mt-0.5 text-indigo-500"></i>
                            <span>{{ service.serviceType.instructionsHelp }}</span>
                        </div>
                    </transition>
                    
                    <label
                        for="instructions"
                        class="block font-semibold text-gray-900 mb-2"
                        >Instructions ou Message <span class="text-sm font-normal text-gray-500">(Optionnel)</span></label
                    >
                    <Textarea
                        id="instructions"
                        v-model="editorInstructions"
                        rows="3"
                        class="w-full"
                        placeholder="Précisez ici vos attentes particulières pour le professionnel..."
                    />
                </div>

                <hr class="border-gray-200" />

                <!-- Upload de médias -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block font-semibold text-gray-900">
                            Vos médias à analyser
                        </label>
                        <span class="text-xs font-normal text-gray-500">Formats : {{ acceptedFileTypesLabel }}</span>
                    </div>

                    <!-- Grille Unifiée des Médias -->
                    <div class="mb-4">
                        <div class="flex flex-wrap gap-4">
                            <!-- Tuile Bouton d'Upload -->
                            <div 
                                class="relative w-24 h-24 rounded-lg shadow-sm border border-indigo-200 overflow-hidden bg-indigo-50 hover:bg-indigo-100 flex flex-col items-center justify-center cursor-pointer transition-colors flex-shrink-0 text-indigo-600 group" 
                                @click="triggerUpload"
                                v-tooltip.top="existingMedias.length > 0 ? 'Ajouter d\'autres fichiers' : 'Ajouter des fichiers'"
                            >
                                <template v-if="isUploading">
                                    <div class="absolute bottom-0 left-0 h-full bg-indigo-200 transition-all duration-300 ease-out z-0" :style="{ width: uploadProgress + '%' }"></div>
                                    <div class="z-10 flex flex-col items-center">
                                        <i class="pi pi-cloud-upload text-2xl mb-1 animate-pulse text-indigo-700"></i>
                                        <span class="text-[10px] font-bold text-center leading-tight px-1 uppercase text-indigo-800">{{ uploadProgress }}%</span>
                                    </div>
                                </template>
                                <template v-else>
                                    <i class="pi pi-plus text-3xl mb-1 group-hover:scale-110 transition-transform z-10"></i>
                                    <span class="text-xs font-semibold text-center leading-tight px-1 uppercase tracking-wide z-10">Ajouter</span>
                                </template>
                            </div>

                            <div
                                v-for="(media, index) in existingMedias"
                                :key="index"
                                class="relative w-24 h-24 rounded-lg shadow-sm border border-gray-200 overflow-hidden group bg-gray-100 flex-shrink-0 cursor-pointer"
                                @click="openGallery(index)"
                                :title="media.originalName || media.name"
                            >
                                <!-- Bouton de suppression flottant (visible au survol de la tuile complète) -->
                                <button
                                    @click.stop="removeMedia(index)"
                                    class="absolute top-1 right-1 z-10 w-6 h-6 bg-white/90 hover:bg-red-50 hover:text-red-500 text-gray-600 rounded-full flex items-center justify-center shadow-sm opacity-0 group-hover:opacity-100 transition-all focus:outline-none"
                                    title="Retirer"
                                >
                                    <i class="pi pi-times text-xs font-bold"></i>
                                </button>
                                
                                <!-- Indicateur Nouveau (pastille) -->
                                <div v-if="media.isNew" class="absolute top-1 left-1 z-10 w-2.5 h-2.5 bg-green-500 rounded-full shadow-sm" title="Nouveau fichier"></div>
                                
                                <!-- Indicateur Erreur (pastille) -->
                                <div v-if="media.transcodingStatus === 'FAILED'" class="absolute top-1 left-1 z-10 w-2.5 h-2.5 bg-red-500 rounded-full shadow-sm" title="Erreur d'optimisation vidéo"></div>

                                <!-- Miniature selon le type de fichier -->
                                <div class="w-full h-full flex items-center justify-center">
                                    <img
                                        v-if="isImage(media.originalName || media.filePath)"
                                        :src="baseApiUrl + '/uploads/media/' + media.filePath"
                                        class="object-cover w-full h-full"
                                        alt="Preview"
                                    />
                                    <!-- INDICATEUR DE TRANSCODAGE FFmpeg -->
                                    <div v-else-if="media.transcodingStatus === 'PENDING' || media.transcodingStatus === 'PROCESSING'"
                                        class="flex flex-col items-center justify-center w-full h-full bg-indigo-50 px-1 text-center"
                                        title="La vidéo est en attente d'encodage sur le serveur..."
                                    >
                                        <i class="pi pi-spin pi-spinner text-indigo-500 text-xl mb-1"></i>
                                        <span class="text-[9px] font-bold text-indigo-800 leading-tight uppercase">Traitement vidéo...</span>
                                    </div>
                                    <!-- Miniature JPG générée par FFmpeg (Terminé) -->
                                    <img
                                        v-else-if="isVideo(media.originalName || media.filePath) && media.transcodingStatus === 'COMPLETED' && media.thumbnailPath"
                                        :src="baseApiUrl + '/uploads/media/' + media.thumbnailPath"
                                        class="object-cover w-full h-full"
                                        alt="Video Thumbnail"
                                    />
                                    <!-- Miniature Vidéo HTML5 Native (Non concerné / Vieux MP4) -->
                                    <video 
                                        v-else-if="isVideo(media.originalName || media.filePath)" 
                                        :src="baseApiUrl + '/uploads/media/' + (media.webFilePath || media.filePath) + '#t=0.5'" 
                                        class="object-cover w-full h-full"
                                        preload="metadata"
                                        muted
                                        playsinline
                                    ></video>
                                    <i v-else-if="isAudio(media.originalName || media.filePath)" class="pi pi-headphones text-orange-500 text-2xl"></i>
                                    <i v-else-if="isPdf(media.originalName || media.filePath)" class="pi pi-file-pdf text-red-500 text-2xl"></i>
                                    <i v-else class="pi pi-file text-gray-500 text-2xl"></i>
                                </div>
                                
                                <!-- Overlay icône loupe au survol -->
                                <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                                    <i class="pi pi-search text-white text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Récapitulatif Volume/Durée Uploadé -->
                    <transition name="fade-slide">
                        <div v-if="existingMedias.length > 0" class="mb-4 bg-gray-50 p-3 rounded-md border border-gray-200 flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-700">Total sélectionné :</span>
                            <div class="text-right">
                                <span v-if="service.serviceType?.discriminator === 'unit'" class="font-bold text-indigo-700">
                                    {{ existingMedias.length }} fichier{{ existingMedias.length > 1 ? 's' : '' }}
                                </span>
                                <span v-else-if="service.serviceType?.discriminator === 'duration'" class="font-bold text-indigo-700">
                                    {{ formattedTotalDuration }}
                                    <span class="text-xs font-normal text-gray-500 ml-1">({{ existingMedias.length }} fichier{{ existingMedias.length > 1 ? 's' : '' }})</span>
                                </span>
                            </div>
                        </div>
                    </transition>

                    <div class="hidden">
                        <FileUpload
                            ref="fileUpload"
                            name="file[]"
                            :url="uploadUrl"
                            :multiple="true"
                            :accept="acceptedFileTypes"
                            :maxFileSize="50000000"
                            :auto="true"
                            @upload="onUploadComplete"
                            @error="onUploadError"
                            @before-send="onBeforeSend"
                            @progress="onUploadProgress"
                            @select="filesSelected = true"
                            @clear="filesSelected = false"
                            :showUploadButton="false"
                            :showCancelButton="false"
                            class="w-full custom-fileupload"
                        >
                            <template #header>
                                <span class="hidden"></span>
                            </template>
                            <template #empty>
                                <span class="hidden"></span>
                            </template>
                            <template #content="{ files }">
                                <span class="hidden"></span>
                            </template>
                        </FileUpload>
                    </div>
                </div>

                <!-- (Instructions transférées en haut) -->
            </div>
            <!-- Fin zone scrollable -->

            <!-- Pied de page fixé en bas -->
            <div
                class="sticky bottom-0 w-full bg-white border-t border-gray-200 p-4 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]"
            >
                <div class="flex items-center justify-between">
                    <div class="flex flex-col">
                        <span
                            class="text-xs text-gray-500 uppercase tracking-wide font-bold"
                            >Total estimé</span
                        >
                        <span class="text-2xl font-bold text-gray-900">{{
                            formatCurrency(dynamicEstimatedPrice)
                        }}</span>
                    </div>
                    <div class="flex items-center gap-4">
                        <span v-if="isAutoSaving" class="text-sm text-gray-500 flex items-center gap-2">
                            <i class="pi pi-spin pi-spinner"></i> Enregistrement...
                        </span>
                        <span v-else class="text-sm text-green-600 flex items-center gap-2 transition-opacity opacity-70">
                            <i class="pi pi-check"></i> Sauvegardé
                        </span>
                        <Button
                            label="Fermer"
                            icon="pi pi-times"
                            class="p-button-secondary p-button-outlined"
                            @click="close"
                        />
                    </div>
                </div>
            </div>
        </div>
    </Sidebar>

    <!-- Visionneuse Multimédia Universelle Réutilisable -->
    <MediaGallery
        v-model:visible="isGalleryVisible"
        :medias="combinedMedias"
        :initialIndex="currentMediaIndex"
        :allowDelete="true"
        :parentModalHasScrollLock="true"
        @remove-media="removeMedia"
    />
</template>

<script>
import Sidebar from "primevue/sidebar";
import Button from "primevue/button";
import FileUpload from "primevue/fileupload";
import Textarea from "primevue/textarea";
import MediaGallery from "./MediaGallery.vue";
import { useCartStore } from "@/stores/cart";
import { useAuthStore } from "@/stores/auth";
import { useOrderEditorStore } from "@/stores/orderEditor";
import { API_BASE_URL } from "@/services/api";
import { useToast } from "primevue/usetoast";

export default {
    name: "OrderConfigModal",
    components: {
        Sidebar,
        Button,
        FileUpload,
        Textarea,
        MediaGallery,
    },
    setup() {
        const toast = useToast();
        return { toast };
    },
    props: {
        visible: {
            type: Boolean,
            default: false,
        },
        service: {
            type: Object,
            required: true,
        },
        professional: {
            type: Object,
            required: true,
        },
        existingItem: {
            type: Object,
            default: null, // Reste utilisé pour l'initialisation depuis le parent
        },
    },
    emits: ["update:visible", "added"],
    data() {
        return {
            filesSelected: false,
            showDetails: false, // Collapse state for description & instructions
            
            // Upload progress state
            isUploading: false,
            uploadProgress: 0,

            // Gallery state
            isGalleryVisible: false,
            currentMediaIndex: 0,

            cartStore: useCartStore(),
            authStore: useAuthStore(),
            editorStore: useOrderEditorStore(),
            baseApiUrl: API_BASE_URL,
        };
    },
    computed: {
        acceptedFileTypes() {
            // Lecture stricte de la validation Base de Données exposée par API Platform
            // htmlAcceptMask peut être vide ou indéfini sur des vieilles données, on garde un fallback
            if (this.service?.serviceType?.htmlAcceptMask) {
                return this.service.serviceType.htmlAcceptMask;
            }
            return "image/*,video/mp4,video/quicktime,application/pdf,audio/*";
        },
        acceptedFileTypesLabel() {
            const types = this.acceptedFileTypes;
            const labels = [];
            if (types.includes('image')) labels.push('Images');
            if (types.includes('video')) labels.push('Vidéos');
            if (types.includes('audio')) labels.push('Audio');
            if (types.includes('pdf')) labels.push('Documents PDF');
            
            return labels.length > 0 ? labels.join(', ') : "Tous fichiers";
        },
        uploadUrl() {
            // Point d'entrée natif d'API Platform (dynamique selon l'env)
            return `${this.baseApiUrl}/api/media_objects`;
        },
        existingMedias() {
            return this.editorStore.medias;
        },
        currentMedia() {
            return this.existingMedias[this.currentMediaIndex] || null;
        },
        isAutoSaving() {
            return this.editorStore.isSaving;
        },
        editorInstructions: {
            get() {
                return this.editorStore.instructions;
            },
            set(val) {
                this.editorStore.updateInstructions(val);
            }
        },
        dynamicExistingItem() {
            // Recherche la ligne de commande potentiellement à jour dans orderEditor ou fallback sur cartStore prop
            const serviceId = this.service?.id;
            const proId = this.professional?.id;
            
            const editorItem = this.editorStore.existingItem;
            if (editorItem) {
                // Sécurité anti-fuite d'état : on s'assure de l'ID correct avant de le retourner
                const editorServiceId = editorItem.service?.id || (typeof editorItem.service === 'string' ? editorItem.service.split('/').pop() : null);
                const editorProId = editorItem.professional?.id || (typeof editorItem.professional === 'string' ? editorItem.professional.split('/').pop() : null);
                
                if (String(editorServiceId) === String(serviceId) && String(editorProId) === String(proId)) {
                    return editorItem;
                }
            }
            
            return this.cartStore.allItems.find(item => 
                (item.service === `/api/pro_services/${serviceId}` || 
                 (item.service && item.service['@id'] === `/api/pro_services/${serviceId}`) ||
                 (item.service && item.service.id === serviceId)) &&
                (item.professional && (item.professional.id === proId || item.professional['@id'] === `/api/professionals/${proId}`))
            ) || this.existingItem;
        },
        combinedMedias() {
             return this.existingMedias;
        },
        dynamicEstimatedPrice() {
            if (!this.service) return 0;
            
            const basePrice = this.service.basePrice || 0;
            const supplementPrice = this.service.supplementPrice || 0;
            const discriminator = this.service.serviceType?.discriminator;
            const mediaCount = this.existingMedias.length;

            if (mediaCount === 0) return basePrice;

            if (discriminator === 'unit') {
                const baseQty = this.service.serviceType?.baseQuantity || 1;
                const extraQty = Math.max(0, mediaCount - baseQty);
                return basePrice + (extraQty * supplementPrice);
            }

            if (discriminator === 'duration') {
                // Front-end approximation for duration. 
                // Actual calculation happens on backend using media duration metadata.
                const totalDurationSeconds = this.totalUploadedDurationSeconds;

                const baseDurationMin = this.service.serviceType?.baseDurationMin || 0;
                const baseDurationSeconds = baseDurationMin * 60;

                if (totalDurationSeconds <= baseDurationSeconds) {
                    return basePrice;
                }

                const diffSeconds = totalDurationSeconds - baseDurationSeconds;
                const extraMinutes = Math.ceil(diffSeconds / 60);

                return basePrice + (extraMinutes * supplementPrice);
            }

            return basePrice;
        },
        totalUploadedDurationSeconds() {
            let total = 0;
            this.existingMedias.forEach(m => {
                // Now m.duration should exist from the API!
                if (m.duration) {
                    total += m.duration;
                } 
            });
            return total;
        },
        formattedTotalDuration() {
            const totalSecs = this.totalUploadedDurationSeconds;
            if (totalSecs === 0) return '0 min';

            const minutes = Math.floor(totalSecs / 60);
            const seconds = totalSecs % 60;
            
            if (minutes === 0) {
                return `${seconds} sec`;
            } else if (seconds === 0) {
                return `${minutes} min`;
            } else {
                return `${minutes} min ${seconds} sec`;
            }
        },
        canSubmit() {
            return true;
        },
    },

    watch: {
        visible: {
            immediate: true,
            handler(newVal) {
                if (newVal) {
                    document.body.style.overflow = "hidden";
                    // Init the editor store with relevant data
                    this.editorStore.init(this.service, this.professional, this.dynamicExistingItem);
                    
                    this.filesSelected = false;
                    this.isGalleryVisible = false;
                } else {
                    document.body.style.overflow = "";
                    this.editorStore.clear();
                }
            },
        },
    },
    methods: {
        formatCurrency(value) {
            return new Intl.NumberFormat("fr-FR", {
                style: "currency",
                currency: "EUR",
            }).format((value || 0) / 100);
        },
        close() {
            this.$emit("update:visible", false);
        },
        triggerUpload() {
            if (this.$refs.fileUpload) {
                // PrimeVue FileUpload cache le bouton file à l'intérieur
                const fileInput = this.$refs.fileUpload.$el.querySelector('input[type="file"]');
                if (fileInput) fileInput.click();
            }
        },
        onBeforeSend(event) {
            this.isUploading = true;
            this.uploadProgress = 0;
            // PrimeVue gère son propre XHR, il faut lui injecter le token à la volée !
            const token = this.authStore.token;
            if (token) {
                event.xhr.setRequestHeader("Authorization", `Bearer ${token}`);
                // Force l'acceptation de json au retour
                event.xhr.setRequestHeader("Accept", "application/ld+json");
            }
            // Le Backend attend une propriété "category", on fixe à "photo" temporairement ou indéfiniment.
            event.formData.append("category", "photo");
        },
        onUploadComplete(event) {
            // Clear FileUpload internal list so PrimeVue doesn't show its own thumbnails below
            if (this.$refs.fileUpload) {
                this.$refs.fileUpload.clear();
                if (this.$refs.fileUpload.uploadedFiles) {
                    this.$refs.fileUpload.uploadedFiles = [];
                }
            }

            // event.xhr.response contiendra le JSON de MediaObject
            try {
                const fileList = JSON.parse(event.xhr.response);

                // Traitement pour supporter l'upload d'API Platform (retourne l'objet)
                if (Array.isArray(fileList)) {
                    this.editorStore.addMedia(fileList);
                } else if (fileList && typeof fileList === 'object') {
                    const finalObject = { ...fileList };
                    if (!finalObject.originalName && finalObject.contentUrl) {
                        finalObject.originalName = finalObject.contentUrl.split('/').pop();
                    }
                    if (!finalObject.filePath && finalObject.contentUrl) {
                         finalObject.filePath = finalObject.contentUrl.split('/').pop();
                    }
                    this.editorStore.addMedia(finalObject);
                }
            } catch (e) {
                console.error("Format de reponse upload invalide", e);
            } finally {
                this.isUploading = false;
                this.uploadProgress = 0;
            }
        },
        onUploadProgress(event) {
            this.uploadProgress = event.progress;
        },
        onUploadError(event) {
            console.error("Erreur Upload", event);
            this.isUploading = false;
            this.uploadProgress = 0;
            alert(
                "Une erreur est survenue lors de l'upload du fichier. Veuillez réessayer.",
            );
        },
        
        // --- MÉTHODES GALLERY ---
        isImage(filename) {
            if (!filename) return false;
            return /\.(jpg|jpeg|png|gif|webp|bmp)$/i.test(filename);
        },
        isVideo(filename) {
            if (!filename) return false;
            return /\.(mp4|mov|avi|wmv|quicktime|webm|mkv)$/i.test(filename);
        },
        isAudio(filename) {
            if (!filename) return false;
            return /\.(mp3|wav|ogg|aac|flac)$/i.test(filename);
        },
        isPdf(filename) {
            if (!filename) return false;
            return /\.(pdf)$/i.test(filename);
        },
        openGallery(index) {
            this.currentMediaIndex = index;
            this.isGalleryVisible = true;
        },
        removeMedia(index) {
            this.editorStore.removeMedia(index);
        },
    },
};
</script>

<style>
/* Override PrimeVue Sidebar default width */
.order-sidebar {
    width: 100% !important;
}

@media (min-width: 768px) {
    .order-sidebar {
        width: 35rem !important;
    }
}

@media (min-width: 1024px) {
    .order-sidebar {
        width: 45rem !important;
    }
}

/* Retrait pur du bouton Cancel qui peut survenir avec auto=true en mode template sur certains navigateurs */
.custom-fileupload .p-fileupload-buttonbar button.p-button-danger {
    display: none !important;
}

/* Nettoyage visuel brut (supprime les bordures et fond natifs du composant) */
.custom-fileupload.p-fileupload {
    border: none !important;
    background: transparent !important;
    padding: 0 !important;
    margin: 0 !important;
}
.custom-fileupload .p-fileupload-buttonbar,
.custom-fileupload .p-fileupload-content {
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

/* Animations d'expansion pour les détails */
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.3s ease-out;
  max-height: 200px;
  opacity: 1;
  overflow: hidden;
}

.fade-slide-enter-from,
.fade-slide-leave-to {
  max-height: 0;
  opacity: 0;
  margin-top: 0 !important;
  margin-bottom: 0 !important;
  padding-top: 0 !important;
  padding-bottom: 0 !important;
}
</style>
