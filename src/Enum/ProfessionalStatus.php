<?php

namespace App\Enum;

enum ProfessionalStatus: string
{
    case PENDING = 'PENDING';         // En attente de validation Admin
    case ACTIVE = 'ACTIVE';           // Validé et visible
    case REJECTED = 'REJECTED';       // Refusé par l'admin
    case UNAVAILABLE = 'UNAVAILABLE'; // Le pro s'est mis en "vacances"
    case SUSPENDED = 'SUSPENDED';     // Puni/Bloqué
}