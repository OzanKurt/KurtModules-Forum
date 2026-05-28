<?php

declare(strict_types=1);

namespace Kurt\Modules\Forum\Badges;

use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Forum\Events\BadgeAwarded;
use Kurt\Modules\Forum\Models\Badge;
use Kurt\Modules\Forum\Models\UserBadge;

/**
 * Orchestrates badge awarding by dispatching events through registered BadgeRule
 * instances. Keeps badges deduplicated via the unique (user_id, badge_id) constraint.
 */
final class BadgeAwarder
{
    /** @var array<int, BadgeRule> */
    private array $rules = [];

    public function register(BadgeRule $rule): void
    {
        $this->rules[] = $rule;
    }

    /**
     * @return array<int, BadgeRule>
     */
    public function rules(): array
    {
        return $this->rules;
    }

    /**
     * Inspect an event, run every registered rule that targets the event's class,
     * and award the matching badge to the resolved user if the rule evaluates true.
     */
    public function handleEvent(object $event): void
    {
        foreach ($this->rules as $rule) {
            if (! in_array($event::class, $rule->appliesAfter(), true)) {
                continue;
            }

            $user = $this->resolveUser($event);
            if (! $user instanceof Model) {
                continue;
            }

            if ($this->userAlreadyHas($user, $rule->badgeSlug())) {
                continue;
            }

            if (! $rule->evaluate($user, $event)) {
                continue;
            }

            $badge = Badge::query()->where('slug', $rule->badgeSlug())->first();
            if (! $badge instanceof Badge) {
                continue;
            }

            /** @var UserBadge $award */
            $award = UserBadge::query()->create([
                'user_id' => $user->getKey(),
                'badge_id' => $badge->id,
                'awarded_at' => now(),
            ]);

            BadgeAwarded::dispatch($user, $badge, $award);
        }
    }

    private function userAlreadyHas(Model $user, string $badgeSlug): bool
    {
        return UserBadge::query()
            ->where('user_id', $user->getKey())
            ->whereHas('badge', fn ($q) => $q->where('slug', $badgeSlug))
            ->exists();
    }

    /**
     * Pull a Model user off the event by walking common attribute paths.
     */
    private function resolveUser(object $event): ?Model
    {
        $user = $this->property($event, 'user');
        if ($user instanceof Model) {
            return $user;
        }

        $post = $this->property($event, 'post');
        if ($post instanceof Model) {
            $owner = $this->property($post, 'user');
            if ($owner instanceof Model) {
                return $owner;
            }
        }

        $thread = $this->property($event, 'thread');
        if ($thread instanceof Model) {
            $owner = $this->property($thread, 'user');
            if ($owner instanceof Model) {
                return $owner;
            }
        }

        $vote = $this->property($event, 'vote');
        if ($vote instanceof Model) {
            $post = $this->property($vote, 'post');
            if ($post instanceof Model) {
                $owner = $this->property($post, 'user');
                if ($owner instanceof Model) {
                    return $owner;
                }
            }
        }

        return null;
    }

    private function property(object $object, string $name): mixed
    {
        if ($object instanceof Model) {
            return $object->getAttribute($name);
        }

        return property_exists($object, $name) ? $object->{$name} : null;
    }
}
