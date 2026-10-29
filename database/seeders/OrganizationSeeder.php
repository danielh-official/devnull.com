<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\CreateCommitTopics;
use App\Enums\ProfileType;
use App\Models\Commit;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Seeder;

final class OrganizationSeeder extends Seeder
{
    public function __construct(private readonly CreateCommitTopics $createCommitTopics)
    {
        //
    }

    /**
     * The @laravel organization, from its public GitHub profile, with a few commits that mention it to follow.
     */
    public function run(): void
    {
        $laravel = Profile::query()->updateOrCreate(['github_id' => '958072'], [
            'type' => ProfileType::Organization,
            'handle' => 'laravel',
            'name' => 'Laravel',
            'avatar_url' => 'https://avatars.githubusercontent.com/u/958072?v=4',
            'bio' => 'The PHP framework for web artisans.',
            'website' => 'https://laravel.com',
            'github_synced_at' => now(),
        ]);

        $users = User::query()->inRandomOrder()->limit(5)->get();

        Commit::factory()->count(5)->sequence(fn (): array => [
            'user_id' => $users->isEmpty() ? User::factory() : $users->random()->id,
            'body' => fake()->randomElement([
                'feat: upgrade to the latest @laravel release',
                'fix: queue workers restart cleanly, thanks @laravel',
                'chore: try Octane from @laravel on the side project',
                'docs: everything I learned reading @laravel source today',
                'feat: ship it with @laravel Cloud',
            ]),
            'created_at' => fake()->dateTimeBetween('-3 days'),
        ])->create()->each(fn (Commit $commit) => $this->createCommitTopics->handle($commit, ['laravel' => $laravel->id]));
    }
}
