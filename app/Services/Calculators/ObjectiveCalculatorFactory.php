<?php

namespace App\Services\Calculators;

use InvalidArgumentException;

class ObjectiveCalculatorFactory
{
    private static array $calculators = [
        'awareness' => AwarenessCalculator::class,
        'leads' => LeadsCalculator::class,
        'sales' => SalesCalculator::class,
        'calls' => CallsCalculator::class,
    ];

    public static function make(string $objective): ObjectiveCalculatorInterface
    {
        if (!isset(self::$calculators[$objective])) {
            throw new InvalidArgumentException("Unknown objective: {$objective}");
        }

        $calculatorClass = self::$calculators[$objective];
        return new $calculatorClass();
    }

    public static function getSupportedObjectives(): array
    {
        return array_keys(self::$calculators);
    }

    public static function isValidObjective(string $objective): bool
    {
        return isset(self::$calculators[$objective]);
    }
}
