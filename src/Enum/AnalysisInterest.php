<?php

namespace App\Enum;

enum AnalysisInterest: string
{
    case YES = 'YES';         // Oui
    case MAYBE = 'MAYBE';     // Peut-être
    case NO = 'NO';           // Pas vraiment / Non
}