<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderTasksRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Interfaces\TaskRepositoryInterface;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class TaskController extends Controller
{
    use AuthorizesRequests;
    /**
     * Inject the TaskRepository interface through constructor.
     */
    public function __construct(protected TaskRepositoryInterface $taskRepository)
    {
    }

    /**
     * Display a listing of the resource for a specific date.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        // Default to today if no date is provided, ensuring consistency
        $date = $request->get('date', now()->toDateString());
        $q = $request->get('q');

        $tasks = $this->taskRepository->getTasksByUserAndDate(auth()->id(), $date, $q);

        return TaskResource::collection($tasks);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Http\Requests\StoreTaskRequest $request
     * @return \App\Http\Resources\TaskResource
     */
    public function store(StoreTaskRequest $request): TaskResource
    {
        $task = $this->taskRepository->createTask(auth()->id(), $request->validated());

        return new TaskResource($task);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\UpdateTaskRequest $request
     * @param int $id
     * @return \App\Http\Resources\TaskResource
     */
    public function update(UpdateTaskRequest $request, int $id): TaskResource
    {
        $task = $this->taskRepository->getTaskById($id);

        if (!$task) {
            abort(404);
        }

        $this->authorize('update', $task);

        $task = $this->taskRepository->updateTask($id, $request->validated());

        return new TaskResource($task);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(int $id): Response
    {
        $task = $this->taskRepository->getTaskById($id);

        if (!$task) {
            abort(404);
        }

        $this->authorize('delete', $task);

        $this->taskRepository->deleteTask($id);

        return response()->noContent();
    }

    /**
     * Reorder tasks for a specific date.
     *
     * @param \App\Http\Requests\ReorderTasksRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reorder(ReorderTasksRequest $request): JsonResponse
    {
        $data = $request->validated();
        $this->taskRepository->reorderTasks(auth()->id(), $data['date'], $data['tasks']);

        return response()->json(['message' => 'Tasks reordered successfully']);
    }
}