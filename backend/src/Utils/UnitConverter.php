<?php
/**
 * Unit Converter - Utilities for converting between units
 *
 * Handles conversions between liters and tons using fuel density
 */

namespace App\Utils;

class UnitConverter
{
    /**
     * Convert liters to tons
     *
     * @param float $liters Amount in liters
     * @param float $density Fuel density in kg/L (default: 0.75 for gasoline)
     * @return float Amount in tons
     */
    public static function litersToTons(float $liters, float $density = 0.75): float
    {
        if ($liters < 0) {
            throw new \InvalidArgumentException("Liters cannot be negative");
        }

        if ($density <= 0) {
            throw new \InvalidArgumentException("Density must be positive");
        }

        // Formula: tons = (liters × density_kg/L) / 1000
        return round(($liters * $density) / 1000, 2);
    }

    /**
     * Convert tons to liters
     *
     * @param float $tons Amount in tons
     * @param float $density Fuel density in kg/L (default: 0.75)
     * @return float Amount in liters
     */
    public static function tonsToLiters(float $tons, float $density = 0.75): float
    {
        if ($tons < 0) {
            throw new \InvalidArgumentException("Tons cannot be negative");
        }

        if ($density <= 0) {
            throw new \InvalidArgumentException("Density must be positive");
        }

        // Formula: liters = (tons × 1000) / density_kg/L
        return round(($tons * 1000) / $density, 2);
    }

    /**
     * Get standard fuel densities
     *
     * @return array<string, float> Map of fuel types to densities
     */
    public static function getStandardDensities(): array
    {
        return [
            'gasoline' => 0.75,   // Бензин
            'diesel' => 0.84,     // Дизель
            'kerosene' => 0.80,   // Керосин
            'fuel_oil' => 0.92,   // Мазут
            'ai-92' => 0.75,      // АИ-92
            'ai-95' => 0.75,      // АИ-95
            'ai-98' => 0.76,      // АИ-98
        ];
    }

    /**
     * Get density for a fuel type
     *
     * @param string $fuelType Fuel type name
     * @return float Density or default 0.75
     */
    public static function getDensity(string $fuelType): float
    {
        $densities = self::getStandardDensities();
        $key = strtolower($fuelType);

        return $densities[$key] ?? 0.75;
    }
}
