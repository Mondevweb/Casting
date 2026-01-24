<?php

namespace App\Enum;

enum DisputeStatus: string
{
    case OPEN = 'OPEN';         // En attente décision admin
    case ACCEPTED = 'ACCEPTED'; // Validée par admin (déclenche relivraison)
    case REJECTED = 'REJECTED'; // Refusée par admin (clôture commande)
}