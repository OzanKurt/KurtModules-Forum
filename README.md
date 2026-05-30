# laravel-modules-forum

Community **forum** module for Laravel: nested boards containing threads of replies, with up/down voting, a moderation queue, per-thread/board subscriptions, and gamified badges.

## Requirements

- PHP 8.4+
- Laravel 12.x or 13.x
- `ozankurt/laravel-modules-core` v2.x

## Installation

```bash
composer require ozankurt/laravel-modules-forum
```

Publish config and migrations:

```bash
php artisan vendor:publish --tag=forum-config
php artisan vendor:publish --tag=forum-migrations
php artisan migrate
```

## Concepts

- **Board** — top-level category (or nested via `parent_id`). Translatable name + description. Boards can be `locked` (no new threads), `archived` (read-only), or `open`.
- **Thread** — belongs to a board; has a title and a first **post**; can be pinned, locked, hidden.
- **Post** — a reply in a thread (the OP is also a Post row, marked `is_root=true`). Supports one-level threaded replies via `parent_id`.
- **Vote** — a user's up/down on a post; unique per `(post_id, user_id)`. Casting the same value twice toggles the vote off (idempotent). Posts and root-post threads store a denormalised `score`.
- **Subscription** — a user follows a thread or a board; polymorphic via `subscribable_type` + `subscribable_id`. Idempotent.
- **ModerationReport** — a user-submitted report against a post; appears in a queue. Pending → Resolved/Dismissed state machine.
- **Badge** — earned achievement. Award rules are pluggable via `BadgeRule` classes; default rules include first-post, ten-posts, hundred-upvotes, first-thread, and a welcome-committer rule for one-year-old accounts.

## What it provides

- Models: `Board`, `Thread`, `Post`, `Vote`, `Subscription`, `ModerationReport`, `Badge`, `UserBadge`.
- Enums: `BoardState`, `Visibility`, `VoteValue`, `ReportState`, `BadgeRarity`.
- `Thread::reply(User, body, ?Post $parent)` — atomic counter increments inside a transaction; dispatches `ThreadReplied`. `PostCreated` fires from the observer.
- `Post::vote(User, VoteValue)` — idempotent + toggling (cast same value twice removes the vote).
- `Post::report(User, reason, ?notes)` — opens a `ModerationReport` and bumps `reported_count`.
- `Thread::subscribe(User)` / `unsubscribe(User)` — polymorphic, idempotent.
- Pluggable badge engine: implement `Kurt\Modules\Forum\Badges\BadgeRule` and register the class in `config('forum.badges.rules')`. `BadgeAwarder::handleEvent($event)` runs every rule whose `appliesAfter()` lists the event class and writes a `UserBadge` row exactly once thanks to the unique `(user_id, badge_id)` index.
- Policies (`BoardPolicy`, `ThreadPolicy`, `PostPolicy`, `ModerationReportPolicy`) with a global `canModerateForum` gate bypass.
- Console commands: `forum:recount`, `forum:award-badges {--user=}`, `forum:demo`.
- Domain events: `ThreadCreated/Locked/Pinned/Hidden/Moved/Replied`, `PostCreated/Edited/Deleted/Hidden/Reported/ScoreChanged`, `VoteCast/Revoked`, `SubscriptionCreated/Removed`, `BadgeAwarded`, `ModerationReportSubmitted/Resolved/Dismissed`.

## Denormalisation + recount

`Board.thread_count`, `Board.post_count`, `Thread.reply_count`, `Thread.score`, and `Post.score` are denormalised counters. The `forum:recount` command rebuilds every counter from raw rows when they drift.

## Badge engine

```php
namespace Kurt\Modules\Forum\Badges;

interface BadgeRule
{
    public function badgeSlug(): string;

    /** @return array<int, class-string> */
    public function appliesAfter(): array;

    public function evaluate(Illuminate\Database\Eloquent\Model $user, object $event): bool;
}
```

`ForumServiceProvider` binds `BadgeAwarder` as a singleton, populates it from `config('forum.badges.rules')`, and listens to every dispatched event via a wildcard `Event::listen('*', ...)`. Inside `handleEvent`, the awarder iterates rules, resolves the user from the event payload, checks `UserBadge` for an existing award, and inserts a row plus dispatches `BadgeAwarded` if the rule evaluates true.

## Filament admin

The package ships parallel admin resource sets for Filament **v3, v4, and v5** —
`BoardResource`, `ThreadResource`, `PostResource`, `ModerationReportResource`,
`BadgeResource`, and `UserBadgeResource`. The correct set is chosen at runtime
from the installed Filament major, so you register a single version-dispatching
plugin on your panel:

```php
use Filament\Panel;
use Kurt\Modules\Forum\Filament\ForumPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(ForumPlugin::make());
}
```

`ForumPlugin::make()` resolves to the matching `V3`/`V4`/`V5` plugin via
`Kurt\Modules\Core\Support\FilamentVersion`. Install whichever Filament major
your app uses — the resources require nothing beyond what the module already
depends on:

```bash
# whichever your app runs
composer require filament/filament:"^3.0|^4.0|^5.0"
composer require filament/spatie-laravel-media-library-plugin:"^3.0|^4.0|^5.0"
```

What the resources give you:

- **Boards** — per-locale (en/tr) translatable name/description; state
  (open/locked/archived) and visibility (public/unlisted/private) enum selects;
  a parent-board select for the tree; a position field; a table with
  state/visibility badges, parent, and the denormalised thread/post counters,
  filtered by state and visibility.
- **Threads** — title, board select, pinned/locked/hidden toggles and score; a
  table with boolean moderation columns, reply count, score and last-post time,
  filtered by board plus ternary pinned/locked/hidden filters.
- **Posts** — thread select, body, `is_root` flag, score and a Spatie
  media-library attachments upload; a moderation queue table sorted by
  `reported_count` with a reports badge that turns red when non-zero.
- **Moderation reports** — a queue defaulting to the pending bucket, with
  resolve/dismiss row actions and resolve/dismiss/delete bulk actions wired to
  the report state machine.
- **Badges** — translatable name/description, rarity enum, icon and active flag,
  with an awarded-count column.
- **User badges** — a read-mostly list + view of awarded badges, filterable by
  badge.

## License

MIT (c) Ozan Kurt
