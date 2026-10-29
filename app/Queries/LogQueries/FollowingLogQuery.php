<?php

declare(strict_types=1);

namespace App\Queries\LogQueries;

use App\Contracts\Queries\LogQuery;
use App\Enums\TopicType;
use App\Models\Commit;
use App\Models\User;
use App\Queries\CommitBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

final readonly class FollowingLogQuery implements LogQuery
{
    public function builder(?User $user): CommitBuilder
    {
        if (! $user instanceof User) {
            return Commit::query()->whereRaw('1 = 0');
        }

        $following = $user->following();
        $followedOrganizations = $user->followedOrganizations();

        return Commit::query()
            ->where(fn (Builder $query): Builder => $query
                ->whereIn('user_id', $following->newPivotQuery()->select($following->getRelatedPivotKeyName()))
                ->orWhere('user_id', $user->id)
                ->orWhereExists(fn (QueryBuilder $query): QueryBuilder => $query
                    ->from('commit_topics')
                    ->whereColumn('commit_topics.commit_id', 'commits.id')
                    ->where('commit_topics.topic_type', TopicType::Organization)
                    ->whereIn('commit_topics.topic_id', $followedOrganizations->newPivotQuery()->select($followedOrganizations->getRelatedPivotKeyName()))))
            ->visibleTo($user)
            ->withoutMutedAuthorsFor($user)
            ->topLevel()
            ->withVisibleReplyCount($user)
            ->withReplyPreviews($user)
            ->withCardDataFor($user)
            ->latest()
            ->latest('id');
    }
}
