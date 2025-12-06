<?php

namespace App\Policies;

use App\Enums\RunStatus;
use App\Models\Run;
use App\Models\User;

class RunPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function view(User $user, Run $run): bool
    {
        return $user->business_id === $run->business_id;
    }

    public function create(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function update(User $user, Run $run): bool
    {
        return $user->business_id === $run->business_id
            && $run->status === RunStatus::Pending;
    }

    public function delete(User $user, Run $run): bool
    {
        return $user->business_id === $run->business_id
            && $run->status !== RunStatus::InProgress;
    }
}
