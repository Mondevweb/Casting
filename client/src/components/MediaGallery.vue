<template>
    <!-- Visionneuse Multimédia Universelle (Dialog) -->
    <Dialog 
        :visible="visible" 
        @update:visible="$emit('update:visible', $event)"
        modal 
        :blockScroll="false"
        :showHeader="false" 
        :dismissableMask="true"
        :pt="{ 
            mask: { class: 'bg-black/80 backdrop-blur-sm' }, 
            root: { class: 'w-full md:w-[80vw] lg:w-[60vw] xl:w-[50vw] m-0 bg-white rounded-xl shadow-2xl border border-gray-100 overflow-hidden' }, 
            content: { class: 'p-0 w-full flex flex-col items-center justify-between overflow-hidden bg-white border-none' } 
        }"
    >
        <template v-if="currentMedia">
            <!-- HEADER FIXE : Navigation & Fermeture (Thème Clair) -->
            <div class="w-full flex-shrink-0 pt-6 pb-4 flex items-center justify-center z-50">
                <div class="flex items-center gap-2 bg-gray-100 border border-gray-200 p-2 rounded-full shadow-sm">
                    <!-- Flèche Précédent -->
                    <button 
                        v-if="medias.length > 1"
                        class="text-gray-600 hover:text-indigo-600 hover:bg-white transition-all duration-200 w-12 h-12 flex items-center justify-center rounded-full cursor-pointer disabled:opacity-30" 
                        @click="prevMedia"
                        title="Média précédent"
                    >
                        <i class="pi pi-chevron-left text-xl"></i>
                    </button>
                    <div v-else class="w-12 h-12"></div> <!-- Spacer si pas de flèche pour garder le centrage -->

                    <!-- Bouton Fermer (Centre, plus gros) -->
                    <button 
                        class="text-gray-700 hover:text-red-600 hover:bg-red-50 transition-all duration-200 w-14 h-14 flex items-center justify-center rounded-full cursor-pointer bg-white shadow-sm border border-gray-200" 
                        @click="$emit('update:visible', false)" 
                        title="Fermer la galerie"
                    >
                        <i class="pi pi-times text-2xl font-bold"></i>
                    </button>

                    <!-- Flèche Suivant -->
                    <button 
                        v-if="medias.length > 1"
                        class="text-gray-600 hover:text-indigo-600 hover:bg-white transition-all duration-200 w-12 h-12 flex items-center justify-center rounded-full cursor-pointer" 
                        @click="nextMedia"
                        title="Média suivant"
                    >
                        <i class="pi pi-chevron-right text-xl"></i>
                    </button>
                    <div v-else class="w-12 h-12"></div> <!-- Spacer si pas de flèche pour garder le centrage -->
                </div>
            </div>

            <!-- Zone de contenu central -->
            <div class="flex-grow w-full flex items-center justify-center relative overflow-hidden px-4 md:px-8 py-4">
                
                <!-- Conteneur Média Dynamique -->
                <div class="relative w-full flex items-center justify-center">
                    <!-- IMAGE -->
                    <img 
                        v-if="isImage(currentMedia.originalName || currentMedia.filePath)" 
                        :src="baseApiUrl + '/uploads/media/' + currentMedia.filePath" 
                        class="max-w-full max-h-[60vh] object-contain rounded-md shadow-md border border-gray-100"
                    />
                    
                    <!-- VIDEO -->
                    <div 
                        v-else-if="isVideo(currentMedia.originalName || currentMedia.filePath) && (currentMedia.transcodingStatus === 'PENDING' || currentMedia.transcodingStatus === 'PROCESSING')" 
                        class="bg-gray-900 rounded-lg p-8 w-[90vw] md:w-[60vw] h-[60vh] max-h-full flex flex-col items-center justify-center shadow-2xl relative text-center"
                    >
                        <i class="pi pi-spin pi-spinner text-indigo-400" style="font-size: 4rem;"></i>
                        <p class="mt-4 text-gray-300 font-medium">Vidéo en cours de traitement pour la compatibilité Web...</p>
                        <p class="mt-1 text-gray-500 text-sm">Veuillez patienter quelques minutes.</p>
                    </div>
                    
                    <video 
                        v-else-if="isVideo(currentMedia.originalName || currentMedia.filePath)" 
                        controls 
                        autoplay
                        :src="baseApiUrl + '/uploads/media/' + (currentMedia.webFilePath || currentMedia.filePath)"
                        class="max-w-full max-h-[60vh] w-[80vw] object-contain rounded-md shadow-md bg-black"
                    >
                        Votre navigateur ne supporte pas la lecture vidéo.
                    </video>

                    <!-- PDF / AUTRE -->
                    <div v-else class="bg-white rounded-lg p-8 w-[90vw] md:w-[60vw] max-h-full flex flex-col shadow-2xl relative">
                        <iframe 
                            v-if="isPdf(currentMedia.originalName || currentMedia.filePath)"
                            :src="baseApiUrl + '/uploads/media/' + currentMedia.filePath" 
                            class="w-full flex-grow border-0 rounded min-h-[50vh]"
                            title="Aperçu PDF"
                        ></iframe>
                        <div v-else class="flex-grow min-h-[40vh] flex flex-col items-center justify-center text-center">
                            <i class="pi pi-file text-gray-400" style="font-size: 5rem;"></i>
                            <p class="mt-4 text-gray-600 font-medium">L'aperçu n'est pas disponible pour ce type de fichier.</p>
                            <a :href="baseApiUrl + '/uploads/media/' + currentMedia.filePath" target="_blank" class="mt-4 text-indigo-600 hover:text-indigo-800 underline flex items-center gap-2">
                                <i class="pi pi-download"></i> Télécharger le fichier
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer FIXE : Informations et actions -->
            <div class="w-full flex-shrink-0 flex justify-between items-center px-6 py-4 z-50 bg-gray-50 border-t border-gray-100">
                <div class="text-gray-700 text-sm font-medium">
                    {{ currentMediaIndex + 1 }} / {{ medias.length }} &nbsp;&mdash;&nbsp; 
                    <span class="font-bold text-gray-900">{{ currentMedia.originalName || 'Fichier' }}</span>
                </div>

                <!-- Bouton de suppression optionnel, activable via prop -->
                <Button 
                    v-if="allowDelete"
                    icon="pi pi-trash" 
                    label="Retirer ce média"
                    class="p-button-danger p-button-sm rounded-full bg-red-50 text-red-600 border border-red-200 hover:bg-red-100 hover:text-red-700 hover:border-red-300 shadow-sm transition-colors duration-200 p-3 flex gap-2 items-center" 
                    @click="onRemoveCurrentMedia" 
                />
            </div>
        </template>
    </Dialog>
</template>

<script>
import Dialog from "primevue/dialog";
import Button from "primevue/button";
import { API_BASE_URL } from "@/services/api";

export default {
    name: "MediaGallery",
    components: {
        Dialog,
        Button,
    },
    props: {
        // Contrôle de visibilité standard v-model
        visible: {
            type: Boolean,
            default: false,
            required: true
        },
        // Tableau d'objets ou de chaînes contenant les infos liées aux médias
        medias: {
            type: Array,
            required: true,
            default: () => []
        },
        // Index initial d'ouverture
        initialIndex: {
            type: Number,
            default: 0
        },
        // Permettre ou non de supprimer les médias
        allowDelete: {
            type: Boolean,
            default: false
        },
        // Prop optionnelle pour signaler à PrimeVue qu'une modale parente englobe cette galerie
        parentModalHasScrollLock: {
            type: Boolean,
            default: false
        }
    },
    emits: ["update:visible", "remove-media"],
    data() {
        return {
            currentMediaIndex: this.initialIndex,
            baseApiUrl: API_BASE_URL,
        };
    },
    computed: {
        currentMedia() {
            return this.medias[this.currentMediaIndex] || null;
        }
    },
    watch: {
        // Resynchro de l'index quand on ouvre à nouveau la galerie
        visible(newVal) {
            if (newVal) {
                this.currentMediaIndex = this.initialIndex;
            } else if (this.parentModalHasScrollLock) {
                // Contournement bug PrimeVue : Le composant Dialog, lors de sa fermeture,
                // enlève potentiellement la classe qui bloque le défilement général.
                 this.$nextTick(() => {
                    setTimeout(() => {
                        document.body.style.overflow = "hidden";
                    }, 100); 
                });
            }
        },
        // Au cas où le parent modifierait l'initialIndex pendanrt l'affichage
        initialIndex(newVal) {
             this.currentMediaIndex = newVal;
        }
    },
    methods: {
        isImage(filename) {
            if (!filename) return false;
            return /\.(jpg|jpeg|png|gif|webp|bmp)$/i.test(filename);
        },
        isVideo(filename) {
            if (!filename) return false;
            return /\.(mp4|mov|avi|wmv|quicktime|webm|mkv)$/i.test(filename);
        },
        isPdf(filename) {
            if (!filename) return false;
            return /\.(pdf)$/i.test(filename);
        },
        nextMedia() {
            if (this.currentMediaIndex < this.medias.length - 1) {
                this.currentMediaIndex++;
            } else {
                this.currentMediaIndex = 0; // Loop retour au début
            }
        },
        prevMedia() {
            if (this.currentMediaIndex > 0) {
                this.currentMediaIndex--;
            } else {
                this.currentMediaIndex = this.medias.length - 1; // Loop retour à la fin
            }
        },
        onRemoveCurrentMedia() {
            // Emission de l'événement au parent avec l'index exact du média dans le tableau medias fourni
            this.$emit('remove-media', this.currentMediaIndex);
            
            // Si c'était le dernier de la liste, on demande la fermeture au parent
            if (this.medias.length <= 1) { // <= 1 car il est "en cours" de suppression côté parent
                this.$emit("update:visible", false);
            } else if (this.currentMediaIndex >= this.medias.length - 1) {
                // Si on a supprimé (ou va supprimer) le tout dernier élément, on recule d'un cran visuellement
                this.currentMediaIndex = this.medias.length - 2; // -2 car le array est sur le point de réduire de 1
            }
        }
    }
};
</script>
