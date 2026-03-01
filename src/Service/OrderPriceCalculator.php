<?php

namespace App\Service;

use App\Entity\DurationServiceType;
use App\Entity\Order;
use App\Entity\OrderLine;
use App\Entity\UnitServiceType;

class OrderPriceCalculator
{
    public function calculate(Order $order): void
    {

        $totalAmount = 0;

        foreach ($order->getOrderLines() as $line) {
            $linePrice = $this->calculateLinePrice($line);
            $line->setLineTotalAmount($linePrice);
            $totalAmount += $linePrice;
        }

        $order->setTotalAmountTtc($totalAmount);
        
        // --- LOGIQUE FINANCIÈRE ---
        // 1. TVA (Fixe à 20% pour l'instant)
        $vatRate = 20.0;
        $order->setAppliedVatPercent($vatRate);

        // 2. Commission (Ex: 15% TTC pour la plateforme)
        $commissionRate = 0.15; 
        $commission = (int) round($totalAmount * $commissionRate);
        $order->setCommissionAmount($commission);

        // 3. Part Pro
        $proAmount = $totalAmount - $commission;
        $order->setProAmount($proAmount);
    }

    public function calculateLinePrice(OrderLine $line): int
    {
        $service = $line->getService(); // ProService entity
        if (!$service) {
            return 0;
        }

        $serviceType = $line->getServiceType(); // AbstractServiceType
        $medias = $line->getMediaObjects();
        $count = count($medias);
        
        $basePrice = $service->getBasePrice() ?? 0; // in cents
        $supplementPrice = $service->getSupplementPrice() ?? 0; // in cents

        // FREEZE PRICES
        $line->setBasePriceFrozen($basePrice);
        $line->setUnitPriceFrozen($supplementPrice);

        // Au lieu de "instanceof", on utilise le type stocké en base ou on s'assure d'avoir la vraie classe
        // car Doctrine charge un "AbstractServiceTypeProxy" qui échoue au instanceof.
        $discriminator = $serviceType->getDiscriminator();

        if ($discriminator === 'unit') {
            // Formula: Base + (max(0, Qty - BaseQty) * SuppPrice)
            // Comme Doctrine a pu charger un Proxy Abstract, on passe par l'implémentation métier :
            // (Note: baseQuantity est dans UnitServiceType. On s'assure de l'accessibilité).
            $baseQty = method_exists($serviceType, 'getBaseQuantity') ? $serviceType->getBaseQuantity() : 1;
            
            $extraQty = max(0, $count - $baseQty);
            return $basePrice + ($extraQty * $supplementPrice);
        }

        if ($discriminator === 'duration') {
            // Formula: Base + (max(0, ceil((TotalDuration - BaseDuration)/60)) * SuppPrice)
            $totalDurationSeconds = 0;
            foreach ($medias as $media) {
                $totalDurationSeconds += method_exists($media, 'getDuration') ? ($media->getDuration() ?? 0) : 0;
            }

            $baseDurationMin = method_exists($serviceType, 'getBaseDurationMin') ? $serviceType->getBaseDurationMin() : 0;
            $baseDurationSeconds = $baseDurationMin * 60;
            
            if ($totalDurationSeconds <= $baseDurationSeconds) {
                return $basePrice;
            }

            $diffSeconds = $totalDurationSeconds - $baseDurationSeconds;
            $extraMinutes = (int) ceil($diffSeconds / 60);

            return $basePrice + ($extraMinutes * $supplementPrice);
        }

        return 0;
    }
}
