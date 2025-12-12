<?php

namespace App\Interfaces;

use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;

interface TaskRepositoryInterface
{
    public function getTasksByUserAndDate(int $userId, string $date, ?string $query = null): Collection;

    public function getTaskById(int $taskId): ?Task;

    public function createTask(int $userId, array $data): Task;

    public function updateTask(int $taskId, array $data): ?Task;

    public function deleteTask(int $taskId): bool;

    public function reorderTasks(int $userId, string $date, array $taskOrders): void;
}
