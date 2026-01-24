<?php

namespace App\Enum;

enum OrderStatus: string
{
    case CART = 'CART'; // Panier (pas encore payé)
    case PENDING_PAYMENT = 'PENDING_PAYMENT'; // En cours de paiement Stripe
    case PAID_PENDING_PRO = 'PAID_PENDING_PRO'; // Payé, en attente de validation du Pro
    case IN_PROGRESS = 'IN_PROGRESS'; // Le Pro travaille dessus
    case DELIVERED = 'DELIVERED'; // Le Pro a rendu son analyse
    case DISPUTE = 'DISPUTE'; // Litige en cours
    case COMPLETED = 'COMPLETED'; // Tout est fini
    case CANCELLED = 'CANCELLED'; // Annulé
}