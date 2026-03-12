<template>
    <div class="relative flex items-center justify-center overflow-hidden bg-white" :class="wrapperClass">
        <canvas ref="pdfCanvas" class="w-full h-full object-contain" v-show="!hasError && isLoaded"></canvas>
        <div v-if="isLoading && !hasError" class="absolute inset-0 flex flex-col items-center justify-center bg-gray-50/80">
            <i class="pi pi-spin pi-spinner text-indigo-500 text-xl mb-1"></i>
            <span class="text-[9px] font-bold text-indigo-800 leading-tight">CHGT PDF...</span>
        </div>
        <div v-if="hasError" class="flex flex-col items-center justify-center w-full h-full bg-red-50 p-2 text-center text-[10px] text-red-600 font-mono overflow-auto break-all">
            <i class="pi pi-file-pdf text-red-500 text-3xl mb-1"></i>
            {{ errorMessage }}
        </div>
    </div>
</template>

<script>
import * as pdfjsLib from 'pdfjs-dist';
import { markRaw } from 'vue';

// Configuration du Worker (indispensable pour pdf.js sans bloquer le Main Thread)
// On pointe vers un CDN de la même version pour éviter les heurts avec Vite
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

export default {
    name: 'PdfThumbnail',
    props: {
        src: {
            type: String,
            required: true
        },
        wrapperClass: {
            type: String,
            default: 'w-full h-full' // Utilisé pour appliquer les marges/arrondis du parent
        }
    },
    data() {
        return {
            isLoading: true,
            isLoaded: false,
            hasError: false,
            errorMessage: '',
            pdfDoc: null
        };
    },
    mounted() {
        this.renderPdf();
    },
    unmounted() {
        if (this.pdfDoc) {
            this.pdfDoc.destroy();
        }
    },
    watch: {
        src() {
            this.renderPdf();
        }
    },
    methods: {
        async renderPdf() {
            if (!this.src) return;
            
            this.isLoading = true;
            this.hasError = false;
            this.isLoaded = false;
            
            try {
                const loadingTask = pdfjsLib.getDocument(this.src);
                const doc = await loadingTask.promise;
                this.pdfDoc = markRaw(doc);
                
                // Récupération de la page 1 uniquement
                const page = await this.pdfDoc.getPage(1);
                
                // Résolution raisonnable pour une miniature
                const scale = 1.0; 
                const viewport = page.getViewport({ scale });
                
                const canvas = this.$refs.pdfCanvas;
                if (!canvas) return; // Sécurité si le composant a été démonté entre temps
                
                const context = canvas.getContext('2d');
                canvas.height = viewport.height;
                canvas.width = viewport.width;
                
                const renderContext = {
                    canvasContext: context,
                    viewport: viewport
                };
                
                await page.render(renderContext).promise;
                this.isLoaded = true;
            } catch (err) {
                console.error('Erreur rendu PDF (Miniature):', err);
                this.errorMessage = err.message || String(err);
                this.hasError = true;
            } finally {
                this.isLoading = false;
            }
        }
    }
}
</script>
