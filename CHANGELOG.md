# Changelog

All notable changes to this project will be documented in this file. The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2026-05-28

Initial release of the `ozankurt/laravel-modules-forum` package.

### Added

- Models: `Board` (translatable, sluggable, soft-deletes, nested via `parent_id`), `Thread` (sluggable, soft-deletes), `Post` (soft-deletes, `HasMedia`, self-referential `parent_id`), `Vote`, `Subscription` (polymorphic), `ModerationReport`, `Badge` (translatable), `UserBadge`.
- Enums: `BoardState` (Open, Locked, Archived), `Visibility` (Public, Unlisted, Private), `VoteValue` (Down=-1, Up=1), `ReportState` (Pending, Resolved, Dismissed), `BadgeRarity` (Common, Uncommon, Rare, Legendary).
- `Thread::reply(Model $user, string $body, ?Post $parent = null): Post` — atomic transaction that creates the post, increments `reply_count`, bumps `last_post_id` + `last_post_at`, increments `Board.post_count`, and dispatches `ThreadReplied`.
- `Post::vote(Model $user, VoteValue $value): ?Vote` — idempotent + toggling. Casting the same value twice removes the vote; opposite value updates it; self-vote rejected unless `forum.allow_self_vote = true`. Recomputes `Post.score` and keeps `Thread.score` in sync for root posts.
- `Post::report(Model $reporter, string $reason, ?string $notes = null): ModerationReport` — opens a pending report and bumps `reported_count`.
- `Thread::subscribe(Model $user)` / `unsubscribe(Model $user)` — polymorphic subscriptions, idempotent.
- `ModerationReport::resolve(Model $handler)` / `dismiss(Model $handler)` state machine with `scopePending`.
- Pluggable badge engine: `BadgeRule` contract + `BadgeAwarder::handleEvent($event)` that runs every rule whose `appliesAfter()` lists the event class, deduplicates awards via the unique `(user_id, badge_id)` constraint, and dispatches `BadgeAwarded`. Default rules ship: `FirstPostBadge`, `TenPostsBadge`, `HundredUpvotesBadge`, `FirstThreadBadge`, `WelcomeCommitterBadge`.
- Console commands: `forum:recount` (rebuilds `Board.thread_count`, `Board.post_count`, `Thread.reply_count`, `Thread.score`, `Thread.last_post_*`, and `Post.score` from raw rows), `forum:award-badges {--user=}` (replays `PostCreated`/`ThreadCreated`/`VoteCast` through `BadgeAwarder` for one or all users), `forum:demo` (seeds boards, threads, posts, votes).
- Events: `ThreadCreated`, `ThreadLocked($thread, $moderator)`, `ThreadPinned`, `ThreadHidden`, `ThreadMoved($thread, $fromBoard, $toBoard, $moderator)`, `ThreadReplied($thread, $post)`, `PostCreated`, `PostEdited`, `PostDeleted`, `PostHidden`, `PostReported`, `PostScoreChanged`, `VoteCast`, `VoteRevoked($post, $user)`, `SubscriptionCreated`, `SubscriptionRemoved`, `BadgeAwarded($user, $badge, $award)`, `ModerationReportSubmitted`, `ModerationReportResolved`, `ModerationReportDismissed`.
- Observers: `PostObserver` (dispatches `PostCreated/Edited/Deleted`, restores counters on soft-delete/restore), `ThreadObserver` (dispatches `ThreadCreated`, maintains `Board.thread_count`).
- Policies: `BoardPolicy`, `ThreadPolicy`, `PostPolicy`, `ModerationReportPolicy` with a global `canModerateForum` gate bypass.
- Wildcard `Event::listen('*', ...)` wiring in `ForumServiceProvider` so every dispatched event passes through `BadgeAwarder::handleEvent`.
- Migrations: `forum_boards`, `forum_threads`, `forum_posts` (with deferred FK from `forum_threads.last_post_id`), `forum_votes`, `forum_subscriptions`, `forum_moderation_reports`, `forum_badges`, `forum_user_badges`.
- Pest 3 test suite covering enum cases, board scopes, thread reply counters, vote idempotency + toggle + self-vote, moderation report state machine, polymorphic subscriptions, badge awarding (with the rule fires once + custom-rule registration + missing-row skip), and `forum:recount` rebuilds.
- GitHub Actions CI (Laravel 12, PHP 8.4) running Pint, PHPStan level 8, and Pest.

### Deferred

- Filament v3/v4/v5 admin resources will land in v2.1. v2.0 is headless.
- Tagging support is deferred — boards/threads do not yet expose a tag pivot.
