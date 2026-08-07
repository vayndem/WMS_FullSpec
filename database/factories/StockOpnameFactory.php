<?php

namespace Database\Factories;

use App\Models\StockOpname;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockOpnameFactory extends Factory
{
    protected $model = StockOpname::class;

    public function definition(): array
    {
        return [
            'number' => 'SO-' . $this->faker->unique()->numerify('########'),
            'warehouse_id' => 1,
            'cutoff_at' => now(),
            'status' => StockOpname::DRAFT,
            'notes' => $this->faker->optional()->sentence(),
            'created_by' => 5,
        ];
    }
}
