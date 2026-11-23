<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Website;

class WebsitePolicy
{
    /**
     * Determine whether the user can view the website.
     */
    public function view(User $user, Website $website): bool
    {
        return $user->isAdmin() || ($user->business && $user->business->id === $website->business_id);
    }

    /**
     * Determine whether the user can update the website.
     */
    public function update(User $user, Website $website): bool
    {
        return $user->isAdmin() || ($user->business && $user->business->id === $website->business_id);
    }

    /**
     * Determine whether the user can publish the website.
     */
    public function publish(User $user, Website $website): bool
    {
        return $user->isAdmin() || ($user->business && $user->business->id === $website->business_id);
    }
}
