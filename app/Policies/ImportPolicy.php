<?php

namespace App\Policies;

use App\Models\Import;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ImportPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Import $import): bool
    {
        return $import->user_id === $user->id;
    }

    /**
     * Determine whether the user can view the model logs.
     */
    public function viewLogs(User $user, Import $import): bool
    {
        return $import->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

}
