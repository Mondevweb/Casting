<?php

namespace App\Enum;

enum DisputeReason: string
{
    case SUPERFICIAL = 'SUPERFICIAL';           // Analyse trop superficielle
    case MISSING_POINTS = 'MISSING_POINTS';     // Points essentiels manquants
    case PARTIAL_TREATMENT = 'PARTIAL_TREATMENT'; // Matériel non traité / partiellement traité
    case MISUNDERSTANDING = 'MISUNDERSTANDING'; // Mauvaise compréhension de ma demande
    case FACTUAL_ERROR = 'FACTUAL_ERROR';       // Erreur factuelle
    case OTHER = 'OTHER';                       // Autre
}