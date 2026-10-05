<?php

namespace Tests\Unit\OrderIntake;

use App\OrderIntake\Catalog;
use App\OrderIntake\DeliveryAreas;
use App\OrderIntake\DraftResolver;
use App\OrderIntake\TextOrderParser;

/** A small in-memory catalogue and zone list — the AI Order pipeline without a database. */
trait IntakeFixtures
{
    protected function catalog(): Catalog
    {
        return new Catalog([
            ['id' => 1, 'name' => 'Sunset Charm 3 Pcs Unstitched Dress', 'code' => 'SF-0156', 'variable' => false, 'price' => 2250.0, 'keywords' => [], 'variants' => []],
            ['id' => 2, 'name' => 'Zareen 4 Pcs Unstitched Dress', 'code' => 'SF-0150', 'variable' => true, 'price' => 2200.0, 'keywords' => ['zarin'], 'variants' => [
                ['id' => 21, 'sku' => 'SF-0150-RED', 'label' => 'Red', 'values' => ['red'], 'price' => 2200.0],
                ['id' => 22, 'sku' => 'SF-0150-BLUE', 'label' => 'Blue', 'values' => ['blue'], 'price' => 2300.0],
            ]],
            ['id' => 3, 'name' => 'Meher Digital Printed 3 Piece', 'code' => 'SF-0170', 'variable' => false, 'price' => 1050.0, 'keywords' => [], 'variants' => []],
            ['id' => 4, 'name' => 'Elara Embroidered Dress', 'code' => 'SF-0180', 'variable' => false, 'price' => 1950.0, 'keywords' => [], 'variants' => []],
            ['id' => 5, 'name' => 'Elara Printed Dress', 'code' => 'SF-0181', 'variable' => false, 'price' => 1650.0, 'keywords' => [], 'variants' => []],
        ]);
    }

    protected function areas(): DeliveryAreas
    {
        return new DeliveryAreas([
            ['method_id' => 10, 'zone' => 'Inside Dhaka', 'label' => 'Inside Dhaka', 'kind' => null, 'is_default' => true],
            ['method_id' => 11, 'zone' => 'Dhaka Sub Area', 'label' => 'Dhaka Sub Area', 'kind' => null, 'is_default' => false],
            ['method_id' => 12, 'zone' => 'Outside Dhaka', 'label' => 'Outside Dhaka', 'kind' => null, 'is_default' => false],
        ], [], ['Bhaluka', 'Kotchandpur']);
    }

    protected function parser(): TextOrderParser
    {
        return new TextOrderParser($this->catalog(), $this->areas());
    }

    /** Delivery: 60 inside Dhaka, 100 sub area, 120 outside. */
    protected function resolver(array $customers = []): DraftResolver
    {
        return new DraftResolver(
            $this->catalog(),
            $this->areas(),
            fn (int $methodId) => [10 => 60.0, 11 => 100.0, 12 => 120.0][$methodId] ?? null,
            $customers,
            10,
        );
    }

    /** @return list<array<string, mixed>> */
    protected function read(string $text, array $customers = []): array
    {
        $resolver = $this->resolver($customers);

        return array_map(fn ($raw) => $resolver->resolve($raw), $this->parser()->parse($text));
    }
}
