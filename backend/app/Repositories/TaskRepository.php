<?php

namespace App\Repositories;

use App\Interfaces\TaskRepositoryInterface;
use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TaskRepository implements TaskRepositoryInterface
{
    /**
     * Fetch all tasks (completed or not) for a specific user and date.
     *
     * @param int $userId
     * @param string $date
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTasksByUserAndDate(int $userId, string $date, ?string $query = null): Collection
    {
        return Task::where('user_id', $userId)
            ->whereDate('due_date', $date)
            ->when($query, function ($q) use ($query) {
                $q->where('statement', 'like', '%' . $query . '%');
            })
            ->orderBy('order')
            ->get();
    }

    /**
     * Get a task by ID.
     *
     * @param int $taskId
     * @return \App\Models\Task|null
     */
    public function getTaskById(int $taskId): ?Task
    {
        return Task::find($taskId);
    }

    /**
     * Create a new task with auto-ordering.
     *
     * @param int $userId
     * @param array $data
     * @return \App\Models\Task
     */
    public function createTask(int $userId, array $data): Task
    {
        $maxOrder = Task::where('user_id', $userId)
            ->whereDate('due_date', $data['due_date'])
            ->max('order');

        return Task::create([
            'user_id' => $userId,
            'statement' => $data['statement'],
            'due_date' => $data['due_date'],
            'is_completed' => false,
            'order' => ($maxOrder ?? 0) + 1,
        ]);
    }

    /**
     * Update an existing task.
     *
     * @param int $taskId
     * @param array $data
     * @return \App\Models\Task|null
     */
    public function updateTask(int $taskId, array $data): ?Task
    {
        $task = $this->getTaskById($taskId);

        if ($task) {
            $task->update($data);
        }

        return $task;
    }

    /**
     * Delete a task by ID.
     *
     * @param int $taskId
     * @return bool
     */
    public function deleteTask(int $taskId): bool
    {
        $task = $this->getTaskById($taskId);

        return $task ? $task->delete() : false;
    }

    /**
     * Reorder tasks for a specific user and date.
     *
     * @param int $userId
     * @param string $date
     * @param array $taskOrders
     * @return void
     */
    public function reorderTasks(int $userId, string $date, array $taskOrders): void
    {
        DB::transaction(function () use ($userId, $date, $taskOrders) {
            foreach ($taskOrders as $item) {
                Task::where('user_id', $userId)
                    ->whereDate('due_date', $date)
                    ->where('id', $item['id'])
                    ->update(['order' => $item['order']]);
            }
        });
    }
}
