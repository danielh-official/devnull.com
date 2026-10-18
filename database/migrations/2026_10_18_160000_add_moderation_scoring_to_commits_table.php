<?php

declare(strict_types=1);

use App\Actions\UpdateCommitModeration;
use App\Enums\CommitStatus;
use App\Models\Commit;
use App\Moderation\DeterministicModerationSignals;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commits', function (Blueprint $table): void {
            $table->unsignedSmallInteger('moderation_score')->default(0);
            $table->json('moderation_signals')->nullable();
            $table->char('body_hash', 64)->nullable();

            $table->index('body_hash');
            $table->index(['status', 'moderation_score', 'created_at']);
        });

        // Hash first so every pending commit can match the pending and rejected ones, whichever order they are scored in.
        $signals = resolve(DeterministicModerationSignals::class);

        Commit::query()
            ->whereIn('status', [CommitStatus::Pending, CommitStatus::Rejected])
            ->orderBy('id')
            ->each(function (Commit $commit) use ($signals): void {
                $commit->forceFill(['body_hash' => $signals->bodyHash($commit->body)])->saveQuietly();
            });

        Commit::query()
            ->where('status', CommitStatus::Pending)
            ->orderBy('id')
            ->each(function (Commit $commit): void {
                resolve(UpdateCommitModeration::class)->handle($commit);
            });
    }

    public function down(): void
    {
        Schema::table('commits', function (Blueprint $table): void {
            $table->dropIndex(['status', 'moderation_score', 'created_at']);
            $table->dropIndex(['body_hash']);
            $table->dropColumn(['moderation_score', 'moderation_signals', 'body_hash']);
        });
    }
};
