<?php

declare(strict_types=1);

use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

it('may follow an organization', function (): void {
    $user = User::factory()->create();
    $laravel = Profile::factory()->organization()->create(['handle' => 'laravel']);

    $response = $this->actingAs($user)
        ->from('/@laravel')
        ->post(route('organization-follow.store', $laravel));

    $response->assertRedirect('/@laravel');

    expect($user->isFollowingOrganization($laravel))->toBeTrue();

    $this->actingAs($user)
        ->post(route('organization-follow.store', $laravel));

    expect($user->followedOrganizations()->count())->toBe(1);
});

it('may unfollow an organization', function (): void {
    $user = User::factory()->create();
    $laravel = Profile::factory()->organization()->create();

    $user->followedOrganizations()->attach($laravel);

    $this->actingAs($user)
        ->delete(route('organization-follow.destroy', $laravel))
        ->assertRedirect();

    expect($user->isFollowingOrganization($laravel))->toBeFalse();
});

it('may not follow the profile of a user as an organization', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($user)
        ->post(route('organization-follow.store', $other->profile))
        ->assertNotFound();

    expect($user->followedOrganizations()->count())->toBe(0);
});

it('requires authentication to follow an organization', function (): void {
    $laravel = Profile::factory()->organization()->create();

    $this->post(route('organization-follow.store', $laravel))
        ->assertRedirectToRoute('home');

    expect(DB::table('organization_follows')->count())->toBe(0);
});

it('tells the organization page whether the viewer follows it', function (): void {
    $user = User::factory()->create();
    $laravel = Profile::factory()->organization()->create(['handle' => 'laravel']);

    $this->actingAs($user)->get('/@laravel')
        ->assertInertia(fn (Assert $page): Assert => $page->where('isFollowing', false));

    $user->followedOrganizations()->attach($laravel);

    $this->actingAs($user)->get('/@laravel')
        ->assertInertia(fn (Assert $page): Assert => $page->where('isFollowing', true));

    $this->get('/@laravel')->assertOk();
});
