<?php

namespace App\Services;

use App\Models\Unit;
use App\Models\UnitConversion;
use InvalidArgumentException;

class UnitConversionService
{
    /**
     * Convert a quantity from one unit to another.
     *
     * @throws InvalidArgumentException
     */
    public function convert(float|string $quantity, Unit|string $fromUnit, Unit|string $toUnit): float
    {
        $qty = (float) $quantity;

        $fromUnitId = $fromUnit instanceof Unit ? $fromUnit->id : $fromUnit;
        $toUnitId = $toUnit instanceof Unit ? $toUnit->id : $toUnit;

        if ($fromUnitId === $toUnitId) {
            return $qty;
        }

        $targetUnit = $toUnit instanceof Unit ? $toUnit : Unit::findOrFail($toUnitId);

        // 1. Direct conversion: From -> To (ex: 1 BOX = 12 U, qty * factor)
        $directConversion = UnitConversion::where('from_unit_id', $fromUnitId)
            ->where('to_unit_id', $toUnitId)
            ->where('is_active', true)
            ->first();

        if ($directConversion) {
            $converted = $qty * (float) $directConversion->factor;
            return round($converted, $targetUnit->precision);
        }

        // 2. Inverse conversion: To -> From (ex: want BOX from U, factor is 12, qty / factor)
        $inverseConversion = UnitConversion::where('from_unit_id', $toUnitId)
            ->where('to_unit_id', $fromUnitId)
            ->where('is_active', true)
            ->first();

        if ($inverseConversion && (float) $inverseConversion->factor > 0) {
            $converted = $qty / (float) $inverseConversion->factor;
            return round($converted, $targetUnit->precision);
        }

        throw new InvalidArgumentException("Aucune règle de conversion active définie entre les unités [{$fromUnitId}] et [{$toUnitId}].");
    }
}
