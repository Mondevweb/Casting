import { defineStore } from 'pinia';
import api from '@/services/api';

export const useCartStore = defineStore('cart', {
    state: () => ({
        // Un panier est en fait un tableau d'entités "Order" ayant le statut CART.
        // Puisqu'un utilisateur peut commander à plusieurs Professionnels, 
        // on peut avoir plusieurs commandes actives.
        orders: [],
        loading: false,
        error: null
    }),
    getters: {
        totalItems: (state) => {
            let count = 0;
            state.orders.forEach(order => {
                count += order.orderLines ? order.orderLines.length : 0;
            });
            return count;
        },
        totalAmount: (state) => {
            let sum = 0;
            state.orders.forEach(order => {
                sum += order.totalAmountTtc || 0;
            });
            return sum;
        },

        // Helper to get all individual items across all orders for the UI
        allItems: (state) => {
            let items = [];
            state.orders.forEach(order => {
                if (order.orderLines) {
                    order.orderLines.forEach(line => {
                        // On attache des informations du parent (Order) pour l'affichage (ex: nom du pro)
                        items.push({
                            ...line,
                            orderId: order.id,
                            professional: order.professional
                        });
                    });
                }
            });
            return items;
        }
    },
    actions: {
        async fetchCart() {
            this.loading = true;
            this.error = null;
            try {
                // Fetch orders that are specifically in CART status
                // The backend automatically filters by the connected Candidate via security rules
                // _t évite le cache agressif du navigateur sur la méthode GET
                const response = await api.get('/orders', {
                    params: { status: 'CART', _t: new Date().getTime() }
                });

                if (response.data['hydra:member']) {
                    this.orders = response.data['hydra:member'];
                } else if (Array.isArray(response.data)) {
                    this.orders = response.data;
                } else {
                    this.orders = [];
                }
            } catch (err) {
                console.error("Failed to fetch cart", err);
                this.error = "Impossible de récupérer votre panier.";
            } finally {
                this.loading = false;
            }
        },

        async addToCart(cartPayload) {
            this.loading = true;
            this.error = null;
            try {
                // cartPayload contient { id(serviceId), professional, mediaObjects, instructions }
                const serviceId = cartPayload.id;
                const proId = cartPayload.professional.id;

                // 1. Check if we already have a CART order for this specific Professional
                const existingOrder = this.orders.find(o =>
                    o.professional === `/api/professionals/${proId}` ||
                    (o.professional && o.professional['@id'] === `/api/professionals/${proId}`) ||
                    (o.professional && o.professional.id === proId)
                );

                const newOrderLine = {
                    service: `/api/pro_services/${serviceId}`,
                    // Quantité à 1 par défaut, évoluera si gestion des "Unités"
                    quantityBilled: 1,
                    instructions: cartPayload.instructions || null,
                    // La modale renvoie déjà un tableau d'IRI (`/api/media_objects/X`)
                    mediaObjects: cartPayload.mediaObjects || []
                };

                if (existingOrder) {
                    // Check if the service already exists as an order line in this order
                    const existingLine = existingOrder.orderLines.find(line => {
                        return line.service === `/api/pro_services/${serviceId}` ||
                            (line.service && line.service['@id'] === `/api/pro_services/${serviceId}`) ||
                            (line.service && line.service.id === serviceId);
                    });

                    if (existingLine) {
                        // MERGE: Update the existing line via PATCH
                        let mergedMedias = [];
                        let mergedInstruc = '';
                        let newQuantity = existingLine.quantityBilled;

                        if (cartPayload.isEdit) {
                            // C'est une édition directe depuis la modale, on écrase les choix précédents
                            mergedMedias = newOrderLine.mediaObjects; // Contient déjà les anciens conservés + nouveaux
                            mergedInstruc = newOrderLine.instructions;
                            // La quantité reste la même lors d'une simple édition de médias/instructions
                        } else {
                            // C'est un ajout depuis 0 alors que ça existait déjà (Ajout incrémental)
                            const existingMedias = existingLine.mediaObjects.map(m => typeof m === 'string' ? m : m['@id']);
                            const newMedias = newOrderLine.mediaObjects;
                            mergedMedias = [...new Set([...existingMedias, ...newMedias])];

                            mergedInstruc = existingLine.instructions || '';
                            if (newOrderLine.instructions) {
                                mergedInstruc += (mergedInstruc ? '\n' : '') + newOrderLine.instructions;
                            }

                            // On incrémente la quantité car c'est un "Ajout au panier" d'un même article
                            newQuantity += 1;
                        }

                        console.log("🛠️ PATCH PANIER Ligne " + existingLine.id, " | Medias finaux: ", mergedMedias);

                        // Si c'est une édition et que le résultat n'a plus aucun média (purge) -> Destruction, même s'il reste du texte
                        if (cartPayload.isEdit && mergedMedias.length === 0) {
                            await api.delete(`/order_lines/${existingLine.id}`);
                        } else {
                            // 3. Patch request normal
                            await api.patch(`/order_lines/${existingLine.id}`, {
                                mediaObjects: mergedMedias,
                                instructions: mergedInstruc,
                                quantityBilled: newQuantity
                            }, {
                                headers: { 'Content-Type': 'application/merge-patch+json' }
                            });
                        }

                    } else {
                        // ADD: Create a new line for this existing order via POST
                        newOrderLine.order = `/api/orders/${existingOrder.id}`;
                        await api.post(`/order_lines`, newOrderLine);
                    }
                } else {
                    // Create a completely new Order for this Professional
                    await api.post('/orders', {
                        professional: `/api/professionals/${proId}`,
                        status: 'CART',
                        orderLines: [newOrderLine]
                    });
                }

                // Refresh full cart to get updated prices and lines from Server
                await this.fetchCart();
                return true;
            } catch (err) {
                console.error("Failed to add to cart", err);
                this.error = "Impossible d'ajouter au panier.";
                throw err;
            } finally {
                this.loading = false;
            }
        },

        async removeFromCart(orderLineId) {
            this.loading = true;
            this.error = null;
            try {
                // Delete the specific order line
                await api.delete(`/order_lines/${orderLineId}`);

                // Refresh cart from server
                await this.fetchCart();
            } catch (err) {
                console.error("Failed to remove from cart", err);
                this.error = "Erreur lors de la suppression de l'article.";
                throw err;
            } finally {
                this.loading = false;
            }
        },

        async checkout() {
            this.loading = true;
            this.error = null;

            // To completely checkout, we need to transition all CART orders to PENDING_PAYMENT
            if (this.orders.length === 0) return true;

            try {
                const promises = this.orders.map(order => {
                    return api.patch(`/orders/${order.id}`, {
                        status: 'PENDING_PAYMENT'
                    }, {
                        headers: {
                            'Content-Type': 'application/merge-patch+json'
                        }
                    });
                });

                await Promise.all(promises);
                this.orders = []; // Local clear, since they are no longer CARTs
                return true;
            } catch (err) {
                console.error("Checkout failed", err);
                this.error = "La validation de la commande a échoué.";
                throw err;
            } finally {
                this.loading = false;
            }
        }
    }
});
