<?php

namespace Tests\Unit;

use App\Services\Calculators\AwarenessCalculator;
use App\Services\Calculators\LeadsCalculator;
use App\Services\Calculators\SalesCalculator;
use App\Services\Calculators\CallsCalculator;
use App\Services\Calculators\ObjectiveCalculatorFactory;
use Tests\TestCase;

class ObjectiveCalculatorTest extends TestCase
{
    public function test_awareness_calculator_computes_correct_kpis(): void
    {
        $calculator = new AwarenessCalculator();
        
        $metrics = [
            'spend' => 1000,
            'impressions' => 100000,
            'reach' => 80000,
            'clicks' => 2000,
            'video_views' => 1500,
        ];

        $kpis = $calculator->calculateKPIs($metrics);

        $this->assertEquals(10.0, $kpis['cpm']); // 1000 / (100000/1000)
        $this->assertEquals(80000, $kpis['reach']);
        $this->assertEquals(1.25, $kpis['frequency']); // 100000 / 80000
        $this->assertEquals(1.5, $kpis['vtr']); // (1500 / 100000) * 100
        $this->assertEquals(2.0, $kpis['ctr']); // (2000 / 100000) * 100
    }

    public function test_leads_calculator_computes_correct_kpis(): void
    {
        $calculator = new LeadsCalculator();
        
        $metrics = [
            'spend' => 500,
            'clicks' => 1000,
            'leads' => 50,
        ];

        $kpis = $calculator->calculateKPIs($metrics);

        $this->assertEquals(10.0, $kpis['cpl']); // 500 / 50
        $this->assertEquals(5.0, $kpis['cvr']); // (50 / 1000) * 100
        $this->assertEquals(0.5, $kpis['cpc']); // 500 / 1000
    }

    public function test_sales_calculator_computes_correct_kpis(): void
    {
        $calculator = new SalesCalculator();
        
        $metrics = [
            'spend' => 1000,
            'revenue' => 5000,
            'purchases' => 25,
        ];

        $kpis = $calculator->calculateKPIs($metrics);

        $this->assertEquals(5.0, $kpis['roas']); // 5000 / 1000
        $this->assertEquals(40.0, $kpis['cpa']); // 1000 / 25
        $this->assertEquals(200.0, $kpis['aov']); // 5000 / 25
    }

    public function test_calls_calculator_computes_correct_kpis(): void
    {
        $calculator = new CallsCalculator();
        
        $metrics = [
            'spend' => 800,
            'calls' => 40,
            'clicks' => 2000,
        ];

        $kpis = $calculator->calculateKPIs($metrics);

        $this->assertEquals(20.0, $kpis['cost_per_call']); // 800 / 40
        $this->assertEquals(40, $kpis['calls']);
        $this->assertEquals(2.0, $kpis['call_conversion_rate']); // (40 / 2000) * 100
    }

    public function test_calculator_handles_zero_division_gracefully(): void
    {
        $calculator = new AwarenessCalculator();
        
        $metrics = [
            'spend' => 1000,
            'impressions' => 0, // This should cause division by zero
            'reach' => 0,
            'clicks' => 0,
            'video_views' => 0,
        ];

        $kpis = $calculator->calculateKPIs($metrics);

        $this->assertNull($kpis['cpm']);
        $this->assertNull($kpis['frequency']);
        $this->assertNull($kpis['vtr']);
        $this->assertNull($kpis['ctr']);
    }

    public function test_objective_calculator_factory_returns_correct_calculator(): void
    {
        $factory = new ObjectiveCalculatorFactory();

        $this->assertInstanceOf(AwarenessCalculator::class, $factory->make('awareness'));
        $this->assertInstanceOf(LeadsCalculator::class, $factory->make('leads'));
        $this->assertInstanceOf(SalesCalculator::class, $factory->make('sales'));
        $this->assertInstanceOf(CallsCalculator::class, $factory->make('calls'));
    }

    public function test_objective_calculator_factory_throws_exception_for_invalid_objective(): void
    {
        $factory = new ObjectiveCalculatorFactory();

        $this->expectException(\InvalidArgumentException::class);
        $factory->make('invalid_objective');
    }

    public function test_calculator_aggregates_multiple_metrics_correctly(): void
    {
        $calculator = new SalesCalculator();
        
        $metricsCollection = [
            [
                'spend' => 500,
                'revenue' => 2500,
                'purchases' => 10,
            ],
            [
                'spend' => 300,
                'revenue' => 1200,
                'purchases' => 8,
            ],
        ];

        $aggregated = $calculator->aggregateMetrics($metricsCollection);

        $this->assertEquals(800, $aggregated['spend']); // 500 + 300
        $this->assertEquals(3700, $aggregated['revenue']); // 2500 + 1200
        $this->assertEquals(18, $aggregated['purchases']); // 10 + 8

        $kpis = $calculator->calculateKPIs($aggregated);
        $this->assertEquals(4.625, $kpis['roas']); // 3700 / 800
        $this->assertEqualsWithDelta(44.44, $kpis['cpa'], 0.01); // 800 / 18
        $this->assertEqualsWithDelta(205.56, $kpis['aov'], 0.01); // 3700 / 18
    }
}
