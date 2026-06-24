<?php

namespace Tests\Unit;

use App\Models\Vehicle;
use App\Models\VehiclePriceRule;
use App\Services\PricingService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class PricingServiceTest extends TestCase
{
    private function vehicle(string $daily, ?string $deposit = '5000.00', Collection $rules = null): Vehicle
    {
        $vehicle = new Vehicle([
            'daily_price' => $daily,
            'deposit_amount' => $deposit,
            'currency' => 'DOP',
        ]);
        $vehicle->setRelation('priceRules', $rules ?? collect());

        return $vehicle;
    }

    public function test_days_rounds_up_per_24h_and_minimum_one(): void
    {
        $service = new PricingService();

        $this->assertSame(2, $service->days(
            CarbonImmutable::parse('2026-07-01 10:00'),
            CarbonImmutable::parse('2026-07-03 10:00'),
        ));

        // 3 horas → mínimo 1 día.
        $this->assertSame(1, $service->days(
            CarbonImmutable::parse('2026-07-01 10:00'),
            CarbonImmutable::parse('2026-07-01 13:00'),
        ));
    }

    public function test_quote_multiplies_daily_price_by_days(): void
    {
        $service = new PricingService();
        $quote = $service->quote(
            $this->vehicle('3000.00'),
            CarbonImmutable::parse('2026-07-01 10:00'),
            CarbonImmutable::parse('2026-07-03 10:00'),
        );

        $this->assertSame(2, $quote['days']);
        $this->assertSame('6000.00', $quote['base_price']);
        $this->assertSame('5000.00', $quote['deposit_amount']);
        $this->assertSame('DOP', $quote['currency']);
    }

    public function test_percent_price_rule_applies_to_daily_price(): void
    {
        $rule = new VehiclePriceRule([
            'type' => 'min_days',
            'min_days' => 2,
            'price_modifier_type' => 'percent',
            'price_modifier_value' => '-10', // 10% de descuento
            'priority' => 10,
        ]);

        $service = new PricingService();
        $quote = $service->quote(
            $this->vehicle('3000.00', '5000.00', collect([$rule])),
            CarbonImmutable::parse('2026-07-01 10:00'),
            CarbonImmutable::parse('2026-07-03 10:00'),
        );

        // 3000 * 0.9 = 2700 por día * 2 días = 5400
        $this->assertSame('2700.00', $quote['daily_price']);
        $this->assertSame('5400.00', $quote['base_price']);
    }
}
