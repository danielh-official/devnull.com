<?php

declare(strict_types=1);

namespace App\Moderation;

use App\Actions\FindFirstUrl;
use App\Enums\CommitStatus;
use App\Models\Commit;

final readonly class DeterministicModerationSignals
{
    public function __construct(private FindFirstUrl $findFirstUrl)
    {
        //
    }

    /**
     * @return list<array{code: string, weight: int}>
     */
    public function handle(Commit $commit): array
    {
        $weights = config('moderation.weights');
        assert(is_array($weights));

        $signals = [];

        if ($this->isDuplicate($commit)) {
            $signals[] = $this->signal('duplicate', $weights);
        }

        if ($this->isAuthorFlood($commit)) {
            $signals[] = $this->signal('author_flood', $weights);
        }

        if ($this->isBurst($commit)) {
            $signals[] = $this->signal('burst', $weights);
        }

        if ($this->findFirstUrl->handle($commit->body) !== null) {
            $signals[] = $this->signal('has_url', $weights);
        }

        if ($this->isInviteLike($commit->body)) {
            $signals[] = $this->signal('invite_like', $weights);
        }

        return $signals;
    }

    public function bodyHash(string $body): string
    {
        $normalized = mb_strtolower(mb_trim((string) preg_replace('/\s+/u', ' ', $body)));

        return hash('sha256', $normalized);
    }

    /**
     * @param  array<string, mixed>  $weights
     * @return array{code: string, weight: int}
     */
    private function signal(string $code, array $weights): array
    {
        return [
            'code' => $code,
            'weight' => (int) ($weights[$code] ?? 0),
        ];
    }

    private function isDuplicate(Commit $commit): bool
    {
        $hash = $commit->body_hash ?? $this->bodyHash($commit->body);

        return Commit::query()
            ->where('body_hash', $hash)
            ->whereKeyNot($commit->id)
            ->whereIn('status', [CommitStatus::Pending, CommitStatus::Rejected])
            ->exists();
    }

    private function isAuthorFlood(Commit $commit): bool
    {
        $threshold = (int) config('moderation.author_flood_threshold');

        $pendingCount = Commit::query()
            ->where('user_id', $commit->user_id)
            ->pending()
            ->count();

        return $pendingCount >= $threshold;
    }

    private function isBurst(Commit $commit): bool
    {
        $count = (int) config('moderation.burst.count');
        $minutes = (int) config('moderation.burst.minutes');

        $recentCount = Commit::query()
            ->where('user_id', $commit->user_id)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->count();

        return $recentCount >= $count;
    }

    private function isInviteLike(string $body): bool
    {
        return preg_match(
            '/\b(join\s+(my|our)\s+(discord|telegram|whatsapp)|dm\s+me|send\s+me\s+(a\s+)?dm|click\s+(the\s+)?link|free\s+crypto|whatsapp\.me|t\.me\/)/iu',
            $body,
        ) === 1;
    }
}
