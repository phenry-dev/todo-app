<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TaskPolicy
{
    /**
     * Determine whether the user can view the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Task $task
     * @return \Illuminate\Auth\Access\Response
     */
    public function view(User $user, Task $task): Response
    {
        return $user->id === $task->user_id
            ? Response::allow()
            : Response::deny('You do not own this task.');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Task $task
     * @return \Illuminate\Auth\Access\Response
     */
    public function update(User $user, Task $task): Response
    {
        return $user->id === $task->user_id
            ? Response::allow()
            : Response::deny('You do not own this task.');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Task $task
     * @return \Illuminate\Auth\Access\Response
     */
    public function delete(User $user, Task $task): Response
    {
        return $user->id === $task->user_id
            ? Response::allow()
            : Response::deny('You do not own this task.');
    }
}

