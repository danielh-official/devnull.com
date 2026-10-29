<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CreateOrganizationFollowRequest;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class OrganizationFollowController
{
    /**
     * Following an organization brings the commits that mention it into the Following log. Inserted unless already
     * there, like a follow of a user.
     */
    public function store(CreateOrganizationFollowRequest $request, #[CurrentUser] User $user, Profile $organization): RedirectResponse
    {
        $user->followedOrganizations()->newPivotStatement()->insertOrIgnore([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back();
    }

    public function destroy(#[CurrentUser] User $user, Profile $organization): RedirectResponse
    {
        $user->followedOrganizations()->detach($organization->id);

        return back();
    }
}
