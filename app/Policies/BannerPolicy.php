<?php

namespace App\Policies;

use App\Models\Banner;
use App\Models\User;

class BannerPolicy
{
    /**
     * Determine whether the user can view the banner.
     */
    public function view(User $user, Banner $banner): bool
    {
        return $user->isAdmin() || ($user->business && $user->business->id === $banner->business_id);
    }

    /**
     * Determine whether the user can update the banner.
     */
    public function update(User $user, Banner $banner): bool
    {
        return $user->isAdmin() || ($user->business && $user->business->id === $banner->business_id);
    }

    /**
     * Determine whether the user can delete the banner.
     */
    public function delete(User $user, Banner $banner): bool
    {
        return $user->isAdmin() || ($user->business && $user->business->id === $banner->business_id);
    }
}
