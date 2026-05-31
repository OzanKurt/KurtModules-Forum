# Changelog

All notable changes to this project will be documented in this file. The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.2.0] - 2026-05-31

### Changed
- Post voting is now stored by the **Interactions** module
  (`ozankurt/laravel-modules-interactions`) instead of the standalone
  `forum_votes` table. `Post` uses the `Voteable` trait and `Post::vote()`
  routes through Interactions' `InteractionManager`, preserving the self-vote
  guard, toggle semantics, `VoteCast`/`VoteRevoked` events, and `score`/`Thread`
  denormalization.
- `HundredUpvotesBadge`, `ThreadCounters`, and `forum:award-badges` now read
  votes from `interactions_interactions`.

### Removed
- `Forum\Models\Vote` and its factory; the `forum_votes` table is migrated into
  `interactions_interactions` and dropped by
  `2026_05_31_000300_migrate_forum_votes_to_interactions`.

### Note
- `VoteCast` now carries `(Post $post, VoteValue $value)` instead of a `Vote`.

## [2.1.0] - 2026-05-30

### Added
- Filament admin resources for **v3, v4, and v5** in parallel: `BoardResource`, `ThreadResource`, `PostResource`, `ModerationReportResource`, `BadgeResource`, `UserBadgeResource` under `src/Filament/V{3,4,5}`.
- Version-dispatching `Kurt\Modules\Forum\Filament\ForumPlugin` facade — register `->plugin(ForumPlugin::make())` on your panel and the matching V{n} resource set is resolved from the installed Filament major via Core's `FilamentVersion`.
- Board form with per-locale (en/tr) translatable name/description, state + visibility enum selects, parent-board select and position; Thread form with board select and pinned/locked/hidden toggles; Post form with thread select, `is_root` flag and a `SpatieMediaLibraryFileUpload` attachments collection; ModerationReport queue (defaulting to pending) with resolve/dismiss row + bulk actions; Badge form with rarity enum, icon and active flag; read-mostly UserBadge list + view.
- Tables: Board state/visibility badges + thread/post counters; Thread boolean moderation columns with board + pinned/locked/hidden filters; Post queue sorted by `reported_count`; Badge rarity badge + awarded count.
- `Post` gains an `attachments` media collection (disk from `forum.media.disk`).
- `filament/spatie-laravel-media-library-plugin` (`^3.0 || ^4.0 || ^5.0`) added to `require-dev` for the post attachments upload.
- Per-Filament-version PHPStan configs (`phpstan-filament-v{3,4,5}.neon`); the base `phpstan.neon` excludes the three version dirs and the dispatching facade.
- CI matrix gains a Filament axis (`3.*`, `4.*`, `5.*`) with a per-major PHPStan step.

## [2.0.1] - 2026-05-30

### Fixed
- Migrations now publish correctly via `vendor:publish --tag=modules-forum-migrations`. The previous bare-name `hasMigrations()` list pointed at non-existent source paths (real files are timestamp-prefixed). Switched to `discoversMigrations()`.

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
