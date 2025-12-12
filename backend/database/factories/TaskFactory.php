<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Task::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'statement' => $this->faker->sentence(),
            'due_date' => $this->faker->date('Y-m-d'),
            'is_completed' => false,
            'order' => $this->faker->numberBetween(1, 10),
        ];
    }

    /**
     * State for a completed task.
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_completed' => true,
            ];
        });
    }

    /**
     * State for a task with a specific date.
     */
    public function withDate(string $date): static
    {
        return $this->state(function (array $attributes) use ($date) {
            return [
                'due_date' => $date,
            ];
        });
    }
}
