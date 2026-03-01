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
                        <h3 class="font-bold text-indigo-900 text-lg">
                            {{ service.serviceType?.name }}
                        </h3>
                        <p v-if="service.serviceType?.description" class="text-xs text-gray-500 mt-1 mb-1 max-w-sm">
                            {{ service.serviceType.description }}
                        </p>
                        <p class="text-sm text-indigo-700 mt-1">
                            Avec {{ professional?.firstName }}
                            {{ professional?.lastName }}
                        </p>
                    </div>
                    <div class="text-right flex flex-col items-end">
                        <span class="font-bold text-indigo-900 text-lg leading-none">{{ formatCurrency(service.basePrice) }}</span>
                        <!-- Variante de prix: Unité (Photos, Pages...) -->
                        <div v-if="service.serviceType?.discriminator === 'unit'" class="text-xs text-indigo-600 mt-1 text-right">
                            <span class="opacity-80">pour {{ service.serviceType.baseQuantity }} {{ service.serviceType.unitName }}{{ service.serviceType.baseQuantity > 1 ? 's' : '' }}</span>
                            <div v-if="service.supplementPrice > 0" class="font-semibold">
                                puis {{ formatCurrency(service.supplementPrice) }} / supp.
                            </div>
                        </div>
                        <!-- Variante de prix: Durée (Audiodescription, Vidéos...) -->
                        <div v-else-if="service.serviceType?.discriminator === 'duration'" class="text-xs text-indigo-600 mt-1 text-right">
                            <span class="opacity-80">pour {{ service.serviceType.baseDurationMin }} min</span>
                            <div v-if="service.supplementPrice > 0" class="font-semibold">
                                puis {{ formatCurrency(service.supplementPrice) }} / {{ service.serviceType.durationStep }} min supp.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Instructions optionnelles en haut pour meilleure UX -->
                <div>
                    <div v-if="service.serviceType?.instructionsHelp" class="text-sm text-indigo-700 bg-indigo-50 p-3 rounded-md mb-3 border border-indigo-100 flex items-start gap-2">
                        <i class="pi pi-info-circle mt-0.5"></i>
                        <span>{{ service.serviceType.instructionsHelp }}</span>
                    </div>
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
                        placeholder="Précisez ici vos attentes particulières pour le professionnel (ex: 'Pouvez-vous vous concentrer sur mon jeu d\'acteur à la 2ème minute ?')"
                    />
                </div>

                <hr class="border-gray-200" />

                <!-- Upload de médias -->
                <div>
                    <label class="block font-semibold text-gray-900 mb-2"
                        >Vos médias à analyser (Photos/Vidéos)</label
                    >

                    <!-- Liste Unifiée des Médias (Existants et Nouvellement Uploadés) -->
                    <div v-if="existingMedias.length > 0" class="mb-4">
                        <p class="text-sm text-gray-700 font-medium mb-2">
                            Fichiers rattachés à cette prestation :
                        </p>
                        <ul class="space-y-2">
                            <li
                                v-for="(media, index) in existingMedias"
                                :key="index"
                                class="flex items-center justify-between bg-white border border-gray-200 p-2 rounded-md hover:bg-gray-50 transition-colors"
                            >
                                <div
                                    class="flex items-center gap-3 overflow-hidden cursor-pointer flex-grow"
                                    @click="openGallery(index)"
                                >
                                    <!-- Miniature selon le type de fichier -->
                                    <div class="relative w-10 h-10 flex-shrink-0 bg-gray-100 rounded overflow-hidden flex items-center justify-center">
                                        <img
                                            v-if="isImage(media.originalName || media.filePath)"
                                            :src="baseApiUrl + '/uploads/media/' + media.filePath"
                                            class="object-cover w-full h-full"
                                            alt="Preview"
                                        />
                                        <!-- INDICATEUR DE TRANSCODAGE FFmpeg -->
                                        <div v-else-if="media.transcodingStatus === 'PENDING' || media.transcodingStatus === 'PROCESSING'"
                                            class="flex flex-col items-center justify-center w-full h-full bg-indigo-50"
                                            title="La vidéo est en cours d'encodage sur le serveur..."
                                        >
                                            <i class="pi pi-spin pi-spinner text-indigo-500 text-lg"></i>
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
                                        <i v-else-if="isAudio(media.originalName || media.filePath)" class="pi pi-headphones text-orange-500 text-lg"></i>
                                        <i v-else-if="isPdf(media.originalName || media.filePath)" class="pi pi-file-pdf text-red-500 text-lg"></i>
                                        <i v-else class="pi pi-file text-gray-500 text-lg"></i>
                                        
                                        <!-- Overlay icône loupe -->
                                        <div class="absolute inset-0 bg-black/30 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity">
                                            <i class="pi pi-search text-white text-xs"></i>
                                        </div>
                                    </div>

                                    <div class="flex flex-col">
                                        <span class="text-sm font-semibold text-gray-700 truncate max-w-[200px]" :title="media.originalName || media.name">
                                            {{ media.originalName || media.name || "Fichier sans nom" }}
                                        </span>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span v-if="media.isNew" class="text-xs px-1.5 py-0.5 bg-green-100 text-green-700 rounded font-medium">Nouveau</span>
                                            <span v-if="media.transcodingStatus === 'PENDING' || media.transcodingStatus === 'PROCESSING'" class="text-xs px-1.5 py-0.5 bg-blue-100 text-blue-700 rounded font-medium truncate" title="Traitement de la compatibilité web">Conversion en cours...</span>
                                            <span v-if="media.transcodingStatus === 'FAILED'" class="text-xs px-1.5 py-0.5 bg-red-100 text-red-700 rounded font-medium truncate" title="Le fichier n'a pas pu être optimisé pour le web">Format Original (Avertissement)</span>
                                        </div>
                                    </div>
                                </div>
                                <Button
                                    icon="pi pi-trash"
                                    class="p-button-danger p-button-text p-button-sm p-button-rounded ml-2"
                                    @click="removeMedia(index)"
                                    title="Retirer"
                                />
                            </li>
                        </ul>
                    </div>

                    <p class="text-sm text-gray-500 mb-3" v-if="!existingItem">
                        Sélectionnez les fichiers que vous souhaitez soumettre
                        au professionnel ({{ acceptedFileTypesLabel }}).
                    </p>
                    <p class="text-sm text-gray-500 mb-3" v-else>
                        Ajouter de nouveaux fichiers à cette prestation ({{ acceptedFileTypesLabel }}) :
                    </p>

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
                        @select="filesSelected = true"
                        @clear="filesSelected = false"
                        :showUploadButton="false"
                        :showCancelButton="false"
                        chooseLabel="Ajouter des fichiers"
                        chooseIcon="pi pi-cloud-upload"
                        invalidFileSizeMessage="{0} : Fichier trop lourd, max {1}."
                        invalidFileTypeMessage="{0} : Format de fichier non autorisé pour ce type de prestation."
                        class="w-full custom-fileupload"
                    >
                        <!-- Remplacement de la zone Drop Native vide -->
                        <template #empty>
                            <div class="m-0 text-center text-gray-500 font-medium py-8 border-2 border-dashed border-gray-300 rounded-lg bg-gray-50 hover:bg-indigo-50 hover:border-indigo-300 transition-colors flex flex-col items-center justify-center gap-3">
                                <i class="pi pi-upload text-3xl text-gray-400"></i>
                                <span>Glissez et déposez vos fichiers ici ou utilisez le bouton ci-dessus.</span>
                            </div>
                        </template>

                        <!-- Surcharge de la vue PrimeVue des fichiers en transit pour masquer la liste redondante -->
                        <template #content="{ files }">
                            <div v-if="files.length > 0" class="py-4 flex flex-col items-center justify-center bg-blue-50 border border-blue-100 rounded-lg">
                                <i class="pi pi-spin pi-spinner text-blue-500 text-2xl mb-2"></i>
                                <span class="text-sm font-medium text-blue-800">Transfert de {{ files.length }} fichier(s) vers le serveur Internet...</span>
                            </div>
                        </template>
                    </FileUpload>
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
                            formatCurrency(service.basePrice)
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
            return this.service?.serviceType?.htmlAcceptMask || "image/*,video/mp4,video/quicktime,application/pdf,audio/*";
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
        onBeforeSend(event) {
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
            }
        },
        onUploadError(event) {
            console.error("Erreur Upload", event);
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
</style>
