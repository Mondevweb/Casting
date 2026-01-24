<?php

namespace App\Service;

class RegionMap
{
    // La liste complète : Clé = Nom de la région, Valeur = Liste des codes départements
    public const REGIONS = [
        'Auvergne-Rhône-Alpes' => [
            '01', '03', '07', '15', '26', '38', '42', '43', '63', '69', '73', '74'
        ],
        'Bourgogne-Franche-Comté' => [
            '21', '25', '39', '58', '70', '71', '89', '90'
        ],
        'Bretagne' => [
            '22', '29', '35', '56'
        ],
        'Centre-Val de Loire' => [
            '18', '28', '36', '37', '41', '45'
        ],
        'Corse' => [
            '2A', '2B'
        ],
        'Grand Est' => [
            '08', '10', '51', '52', '54', '55', '57', '67', '68', '88'
        ],
        'Hauts-de-France' => [
            '02', '59', '60', '62', '80'
        ],
        'Île-de-France' => [
            '75', '77', '78', '91', '92', '93', '94', '95'
        ],
        'Normandie' => [
            '14', '27', '50', '61', '76'
        ],
        'Nouvelle-Aquitaine' => [
            '16', '17', '19', '23', '24', '33', '40', '47', '64', '79', '86', '87'
        ],
        'Occitanie' => [
            '09', '11', '12', '30', '31', '32', '34', '46', '48', '65', '66', '81', '82'
        ],
        'Pays de la Loire' => [
            '44', '49', '53', '72', '85'
        ],
        'Provence-Alpes-Côte d\'Azur' => [
            '04', '05', '06', '13', '83', '84'
        ],
        'Outre-Mer' => [
            '971', '972', '973', '974', '976' // Guadeloupe, Martinique, Guyane, Réunion, Mayotte
        ]
    ];

    /**
     * Récupère la liste des départements pour une région donnée.
     * Insensible à la casse (accepte "occitanie" ou "Occitanie").
     */
    public static function getDepartmentsFor(string $regionName): array
    {
        // On parcourt le tableau pour trouver la clé correspondante sans se soucier des majuscules
        foreach (self::REGIONS as $name => $departments) {
            if (mb_strtolower($name) === mb_strtolower($regionName)) {
                return $departments;
            }
        }

        return [];
    }

    /**
     * Récupère la liste simple de tous les noms de régions (utile pour un menu déroulant).
     */
    public static function getRegionNames(): array
    {
        return array_keys(self::REGIONS);
    }
}