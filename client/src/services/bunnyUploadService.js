import * as tus from 'tus-js-client';
import axios from 'axios';
import { useAuthStore } from '@/stores/auth';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'https://127.0.0.1:8000';

export async function uploadVideoToBunny(file, serviceTypeId, onProgress) {
    const authStore = useAuthStore();
    if (!authStore.token) throw new Error("Non authentifié");

    // 1. Demander la signature au Backend
    const ticketRes = await axios.post(`${API_BASE_URL}/api/bunny-stream/create-video`, {
        title: file.name
    }, {
        headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Accept': 'application/json'
        }
    });

    const { videoId, libraryId, authorizationSignature, authorizationExpire } = ticketRes.data;

    if (!videoId || !libraryId || !authorizationSignature || !authorizationExpire) {
        throw new Error("Impossible de générer la signature d'upload Bunny");
    }

    // 2. Initialiser l'upload TUS vers Bunny CDN
    return new Promise((resolve, reject) => {
        const upload = new tus.Upload(file, {
            endpoint: "https://video.bunnycdn.com/tusupload",
            retryDelays: [0, 3000, 5000, 10000, 20000],
            headers: {
                AuthorizationSignature: authorizationSignature,
                AuthorizationExpire: authorizationExpire,
                VideoId: videoId,
                LibraryId: libraryId,
            },
            metadata: {
                filetype: file.type,
                title: file.name,
            },
            onError: function (error) {
                console.error("Failed because: " + error);
                reject(error);
            },
            onProgress: function (bytesUploaded, bytesTotal) {
                const percentage = ((bytesUploaded / bytesTotal) * 100).toFixed(0);
                if (onProgress) onProgress(percentage);
            },
            onSuccess: async function () {
                // 3. Notifier le backend que la video a terminé son upload TUS
                try {
                    const finalizeRes = await axios.post(`${API_BASE_URL}/api/bunny-stream/finalize-video`, {
                        videoId: videoId,
                        serviceTypeId: serviceTypeId,
                        originalName: file.name
                    }, {
                        headers: {
                            'Authorization': `Bearer ${authStore.token}`,
                            'Accept': 'application/json'
                        }
                    });
                    
                    resolve(finalizeRes.data);
                } catch (err) {
                    console.error("Échec de la finalisation backend", err);
                    reject(err);
                }
            }
        });

        // Lancer l'upload
        upload.start();
    });
}
