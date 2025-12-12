<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Task;
use Tests\TestCase;

describe('authenticated user', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'sanctum');
    });

    // ========== INDEX ENDPOINT TESTS ==========

    test('can fetch their tasks for a specific date', function () {
        $date = now()->toDateString();
        Task::factory()->for($this->user)->withDate($date)->create(['statement' => 'My Task 1']);
        Task::factory()->withDate($date)->create(['statement' => 'Other Users Task']);

        $response = $this->getJson("/api/tasks?date={$date}");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['statement' => 'My Task 1'])
            ->assertJsonMissing(['statement' => 'Other Users Task']);
    });

    test('index endpoint defaults to today when no date is provided', function () {
        $today = now()->toDateString();
        Task::factory()->for($this->user)->withDate($today)->create(['statement' => 'Today Task']);
        Task::factory()->for($this->user)->withDate(now()->addDay()->toDateString())->create(['statement' => 'Tomorrow Task']);

        $response = $this->getJson('/api/tasks');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['statement' => 'Today Task'])
            ->assertJsonMissing(['statement' => 'Tomorrow Task']);
    });

    test('index returns empty collection when user has no tasks for date', function () {
        $response = $this->getJson("/api/tasks?date=" . now()->toDateString());

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    });

    // ========== STORE ENDPOINT TESTS ==========

    test('can create a task', function () {
        $date = now()->toDateString();
        $taskData = [
            'statement' => 'A new task statement',
            'due_date' => $date,
        ];

        $response = $this->postJson('/api/tasks', $taskData);

        $response->assertStatus(201)
            ->assertJsonFragment(['statement' => 'A new task statement']);

        // SQLite stores dates as datetime strings and booleans as integers
        $this->assertDatabaseHas('tasks', [
            'statement' => 'A new task statement',
            'user_id' => $this->user->id,
            'due_date' => $date . ' 00:00:00', // SQLite stores as datetime
            'is_completed' => 0, // SQLite stores boolean as integer
        ]);
    });

    test('task creation assigns correct order based on existing tasks', function () {
        $date = now()->toDateString();
        Task::factory()->for($this->user)->withDate($date)->create(['order' => 1]);
        Task::factory()->for($this->user)->withDate($date)->create(['order' => 2]);

        $response = $this->postJson('/api/tasks', [
            'statement' => 'Third task',
            'due_date' => $date,
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['order' => 3]);
    });

    test('task creation with missing statement field fails validation', function () {
        $response = $this->postJson('/api/tasks', [
            'due_date' => now()->toDateString(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['statement']);
    });

    test('task creation with missing due_date field fails validation', function () {
        $response = $this->postJson('/api/tasks', [
            'statement' => 'Task without date',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['due_date']);
    });

    test('task creation with invalid date format fails validation', function () {
        $response = $this->postJson('/api/tasks', [
            'statement' => 'Task with bad date',
            'due_date' => 'not-a-date',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['due_date']);
    });

    test('task creation with statement exceeding max length fails validation', function () {
        $response = $this->postJson('/api/tasks', [
            'statement' => str_repeat('x', 256),
            'due_date' => now()->toDateString(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['statement']);
    });

    // ========== UPDATE ENDPOINT TESTS ==========

    test('can update their task', function () {
        $task = Task::factory()->for($this->user)->create();

        $updateData = [
            'statement' => 'Updated statement',
            'is_completed' => true,
        ];

        $response = $this->putJson("/api/tasks/{$task->id}", $updateData);

        $response->assertOk()
            ->assertJsonFragment($updateData);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'statement' => 'Updated statement',
            'is_completed' => 1, // SQLite stores boolean as integer
        ]);
    });

    test('can partially update task (statement only)', function () {
        $task = Task::factory()->for($this->user)->create(['statement' => 'Original']);

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'statement' => 'Updated only statement',
        ]);

        $response->assertOk()
            ->assertJsonFragment(['statement' => 'Updated only statement']);

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'statement' => 'Updated only statement']);
    });

    test('can partially update task (is_completed only)', function () {
        $task = Task::factory()->for($this->user)->create(['is_completed' => false]);

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'is_completed' => true,
        ]);

        $response->assertOk()
            ->assertJsonFragment(['is_completed' => true]);

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'is_completed' => 1]); // SQLite stores boolean as integer
    });

    test('cannot update another users task (policy check)', function () {
        $otherTask = Task::factory()->create();

        $response = $this->putJson("/api/tasks/{$otherTask->id}", [
            'statement' => 'Hacking attempt',
        ]);

        $response->assertForbidden();
    });

    test('update fails with invalid statement length', function () {
        $task = Task::factory()->for($this->user)->create();

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'statement' => str_repeat('x', 256),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['statement']);
    });

    test('update fails with invalid is_completed type', function () {
        $task = Task::factory()->for($this->user)->create();

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'is_completed' => 'not-a-boolean',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['is_completed']);
    });

    test('updating non-existent task returns 404', function () {
        $response = $this->putJson('/api/tasks/99999', [
            'statement' => 'Update nonexistent',
        ]);

        $response->assertNotFound();
    });

    // ========== DELETE ENDPOINT TESTS ==========

    test('can delete their task', function () {
        $task = Task::factory()->for($this->user)->create();

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    });

    test('cannot delete another users task (policy check)', function () {
        $otherTask = Task::factory()->create();

        $response = $this->deleteJson("/api/tasks/{$otherTask->id}");

        $response->assertForbidden();
    });

    test('deleting non-existent task returns 404', function () {
        $response = $this->deleteJson('/api/tasks/99999');

        $response->assertNotFound();
    });

    // ========== REORDER ENDPOINT TESTS ==========

    test('can reorder their tasks', function () {
        $date = now()->toDateString();
        $task1 = Task::factory()->for($this->user)->withDate($date)->create(['order' => 1]);
        $task2 = Task::factory()->for($this->user)->withDate($date)->create(['order' => 2]);
        $task3 = Task::factory()->for($this->user)->withDate($date)->create(['order' => 3]);

        $response = $this->postJson('/api/tasks/reorder', [
            'date' => $date,
            'tasks' => [
                ['id' => $task3->id, 'order' => 1],
                ['id' => $task1->id, 'order' => 2],
                ['id' => $task2->id, 'order' => 3],
            ],
        ]);

        $response->assertOk()
            ->assertJsonFragment(['message' => 'Tasks reordered successfully']);

        $this->assertDatabaseHas('tasks', ['id' => $task3->id, 'order' => 1]);
        $this->assertDatabaseHas('tasks', ['id' => $task1->id, 'order' => 2]);
        $this->assertDatabaseHas('tasks', ['id' => $task2->id, 'order' => 3]);
    });

    test('reorder endpoint fails when date is missing', function () {
        $response = $this->postJson('/api/tasks/reorder', [
            'tasks' => [
                ['id' => 1, 'order' => 1],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    });

    test('reorder endpoint fails when tasks array is missing', function () {
        $response = $this->postJson('/api/tasks/reorder', [
            'date' => now()->toDateString(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tasks']);
    });

    test('reorder endpoint fails with invalid date format', function () {
        $response = $this->postJson('/api/tasks/reorder', [
            'date' => 'invalid-date',
            'tasks' => [
                ['id' => 1, 'order' => 1],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    });

    test('reorder endpoint fails when task id is missing', function () {
        $response = $this->postJson('/api/tasks/reorder', [
            'date' => now()->toDateString(),
            'tasks' => [
                ['order' => 1],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tasks.0.id']);
    });

    test('reorder endpoint fails when task order is missing', function () {
        $response = $this->postJson('/api/tasks/reorder', [
            'date' => now()->toDateString(),
            'tasks' => [
                ['id' => 1],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tasks.0.order']);
    });

    test('reorder endpoint fails with non-existent task id', function () {
        $response = $this->postJson('/api/tasks/reorder', [
            'date' => now()->toDateString(),
            'tasks' => [
                ['id' => 99999, 'order' => 1],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tasks.0.id']);
    });

    test('reorder endpoint fails when order is not an integer', function () {
        $task = Task::factory()->for($this->user)->create();

        $response = $this->postJson('/api/tasks/reorder', [
            'date' => now()->toDateString(),
            'tasks' => [
                ['id' => $task->id, 'order' => 'invalid'],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tasks.0.order']);
    });

    test('reorder endpoint fails with negative order', function () {
        $task = Task::factory()->for($this->user)->create();

        $response = $this->postJson('/api/tasks/reorder', [
            'date' => now()->toDateString(),
            'tasks' => [
                ['id' => $task->id, 'order' => -1],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tasks.0.order']);
    });

    // ========== RESPONSE FORMAT TESTS ==========

    test('task resource includes all required fields', function () {
        $date = now()->toDateString();
        $task = Task::factory()->for($this->user)->withDate($date)->create([
            'statement' => 'Test Task',
            'is_completed' => true,
            'order' => 1,
        ]);

        $response = $this->getJson("/api/tasks?date={$date}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'statement',
                        'is_completed',
                        'due_date',
                        'order',
                        'created_at',
                    ],
                ],
            ]);
    });

    test('collection response has proper structure', function () {
        $date = now()->toDateString();
        Task::factory()->for($this->user)->withDate($date)->count(3)->create();

        $response = $this->getJson("/api/tasks?date={$date}");

        $response->assertOk()
            ->assertJsonStructure(['data'])
            ->assertJsonCount(3, 'data');
    });
});

describe('unauthenticated user', function () {
    test('cannot fetch tasks', function () {
        $response = $this->getJson('/api/tasks');

        $response->assertUnauthorized();
    });

    test('cannot create a task', function () {
        $response = $this->postJson('/api/tasks', [
            'statement' => 'Unauthorized task',
            'due_date' => now()->toDateString(),
        ]);

        $response->assertUnauthorized();
    });

    test('cannot update a task', function () {
        $task = Task::factory()->create();

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'statement' => 'Updated',
        ]);

        $response->assertUnauthorized();
    });

    test('cannot delete a task', function () {
        $task = Task::factory()->create();

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertUnauthorized();
    });

    test('cannot reorder tasks', function () {
        $response = $this->postJson('/api/tasks/reorder', [
            'date' => now()->toDateString(),
            'tasks' => [
                ['id' => 1, 'order' => 1],
            ],
        ]);

        $response->assertUnauthorized();
    });
});

