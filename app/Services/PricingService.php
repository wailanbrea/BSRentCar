<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\VehiclePriceRule;
use Carbon\CarbonImmutable;

/**
 * Cálculo de precios de renta. Dinero con BCMath (nunca float).
 * Ver docs/09_PAYMENTS_WALLET.md (§11) y docs/02_BUSINESS_RULES.md (BR-V03).
 */
class PricingService
{
    private const SCALE = 2;

    /**
     * Número de días de renta (mínimo 1; se redondea hacia arriba por periodo de 24h).
     */
    public function days(\DateTimeInterface $start, \DateTimeInterface $end): int
    {
        $startC = CarbonImmutable::instance($start);
        $endC = CarbonImmutable::instance($end);

        $hours = $startC->diffInHours($endC);

        return max(1, (int) ceil($hours / 24));
    }

    /**
     * Precio diario efectivo tras aplicar la regla aplicable de mayor prioridad.
     */
    public function effectiveDailyPrice(Vehicle $vehicle, \DateTimeInterface $start, int $days): string
    {
        $base = (string) $vehicle->daily_price;

        $rule = $vehicle->priceRules
            ->filter(fn (VehiclePriceRule $r) => $this->ruleApplies($r, $start, $days))
            ->sortByDesc('priority')
            ->first();

        if (! $rule) {
            return $this->round($base);
        }

        if ($rule->price_modifier_type === 'fixed') {
            return $this->round((string) $rule->price_modifier_value);
        }

        // percent: base * (1 + value/100)
        $factor = bcadd('1', bcdiv((string) $rule->price_modifier_value, '100', 6), 6);

        return $this->round(bcmul($base, $factor, 6));
    }

    /**
     * Cotización de la renta de un vehículo para un rango.
     *
     * @return array{days:int, daily_price:string, base_price:string, deposit_amount:string, currency:string}
     */
    public function quote(Vehicle $vehicle, \DateTimeInterface $start, \DateTimeInterface $end): array
    {
        $days = $this->days($start, $end);
        $daily = $this->effectiveDailyPrice($vehicle, $start, $days);
        $basePrice = $this->round(bcmul($daily, (string) $days, 6));
        $deposit = $vehicle->deposit_amount !== null
            ? $this->round((string) $vehicle->deposit_amount)
            : $this->round('0');

        return [
            'days' => $days,
            'daily_price' => $daily,
            'base_price' => $basePrice,
            'deposit_amount' => $deposit,
            'currency' => $vehicle->currency,
        ];
    }

    /**
     * ITBIS sobre el monto gravable (BR-P12). tax_rate desde config/rentcar.php.
     */
    public function tax(string $taxableAmount): string
    {
        $rate = (string) config('rentcar.tax_rate', 0.18);

        return $this->round(bcmul($taxableAmount, $rate, 6));
    }

    public function add(string ...$amounts): string
    {
        $sum = '0';
        foreach ($amounts as $amount) {
            $sum = bcadd($sum, $amount, 6);
        }

        return $this->round($sum);
    }

    private function ruleApplies(VehiclePriceRule $rule, \DateTimeInterface $start, int $days): bool
    {
        if ($rule->min_days !== null && $days < $rule->min_days) {
            return false;
        }

        if ($rule->start_date !== null && CarbonImmutable::instance($start)->lt($rule->start_date)) {
            return false;
        }

        if ($rule->end_date !== null && CarbonImmutable::instance($start)->gt($rule->end_date)) {
            return false;
        }

        return true;
    }

    private function round(string $amount): string
    {
        return bcadd($amount, '0', self::SCALE);
    }
}
