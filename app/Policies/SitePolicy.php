<?php

namespace App\Policies;

use App\Domain\Sites\Models\Site;
use App\Models\User;

final class SitePolicy
{
    public function manage(User $user, Site $site): bool
    {
        return (int) $user->getAuthIdentifier() === $site->owner_user_id;
    }
}
