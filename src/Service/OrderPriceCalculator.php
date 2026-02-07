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

    private function calculateLinePrice(OrderLine $line): int
    {
        $service = $line->getService(); // ProService entity
        if (!$service) {
            return 0;
        }

        $serviceType = $line->getServiceType(); // AbstractServiceType
        $medias = $line->getMediaObjects();
        $count = count($medias);
        
        $basePrice = $service->getBasePrice(); // in cents
        $supplementPrice = $service->getSupplementPrice(); // in cents

        // FREEZE PRICES
        $line->setBasePriceFrozen($basePrice);
        $line->setUnitPriceFrozen($supplementPrice);

        if ($serviceType instanceof UnitServiceType) {
            // Formula: Base + (max(0, Qty - BaseQty) * SuppPrice)
            $baseQty = $serviceType->getBaseQuantity() ?? 1;
            
            $extraQty = max(0, $count - $baseQty);
            return $basePrice + ($extraQty * $supplementPrice);
        }

        if ($serviceType instanceof DurationServiceType) {
            // Formula: Base + (max(0, ceil((TotalDuration - BaseDuration)/60)) * SuppPrice)
            // Note: Duration is in MINUTES in ServiceType, but Medias usually have duration in SECONDS ?
            // Let's check MediaObject::duration (int, assumed seconds or minutes? Name says duration, usually seconds for video). 
            // In the test we set 320 for 5m20s. So it is seconds.
            // ServiceType::baseDurationMin is in MINUTES.
            
            $totalDurationSeconds = 0;
            foreach ($medias as $media) {
                $totalDurationSeconds += $media->getDuration() ?? 0;
            }

            $baseDurationSeconds = ($serviceType->getBaseDurationMin() ?? 0) * 60;
            
            if ($totalDurationSeconds <= $baseDurationSeconds) {
                return $basePrice;
            }

            $diffSeconds = $totalDurationSeconds - $baseDurationSeconds;
            // "Minute entamée est due" => ceil of minutes.
            // So ceil(diffSeconds / 60)
            $extraMinutes = (int) ceil($diffSeconds / 60);

            return $basePrice + ($extraMinutes * $supplementPrice);
        }

        return 0;
    }
}
