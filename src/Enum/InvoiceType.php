<?php

namespace App\Enum;

enum InvoiceType: string
{
    case CANDIDATE_RECEIPT = 'CANDIDATE_RECEIPT'; // Reçu fiscal pour le candidat (Total TTC)
    case PRO_COMMISSION = 'PRO_COMMISSION'; // Facture de commission (Plateforme -> Pro)
}