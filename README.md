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
- `Thread::markSolution(Post)` / `unmarkSolution()` — set/clear the thread's accepted answer (`solution_post_id`); dispatch `SolutionMarked` / `SolutionUnmarked`; guarded by the `markSolution`/`unmarkSolution` policy abilities (thread author, or a moderator via `canModerateForum`). `Post::isSolution()` and the `Thread` `solved()`/`unsolved()` scopes read the state.
- `Thread::search(term)` — find threads by title or post body, distinct and ranked (title matches first, then most recent).
- `Post::vote(User, VoteValue)` — idempotent + toggling (cast same value twice removes the vote).
- `Post::report(User, reason, ?notes)` — opens a `ModerationReport` and bumps `reported_count`.
- `Thread::subscribe(User)` / `unsubscribe(User)` — polymorphic, idempotent.
- Pluggable badge engine: implement `Kurt\Modules\Forum\Badges\BadgeRule` and register the class in `config('forum.badges.rules')`. `BadgeAwarder::handleEvent($event)` runs every rule whose `appliesAfter()` lists the event class and writes a `UserBadge` row exactly once thanks to the unique `(user_id, badge_id)` index.
- Policies (`BoardPolicy`, `ThreadPolicy`, `PostPolicy`, `ModerationReportPolicy`) with a global `canModerateForum` gate bypass.
- Console commands: `forum:recount`, `forum:award-badges {--user=}`, `forum:demo`.
- Domain events: `ThreadCreated/Locked/Pinned/Hidden/Moved/Replied`, `SolutionMarked/Unmarked`, `PostCreated/Edited/Deleted/Hidden/Reported/ScoreChanged`, `VoteCast/Revoked`, `SubscriptionCreated/Removed`, `BadgeAwarded`, `ModerationReportSubmitted/Resolved/Dismissed`.

## Best answer (solutions)

A thread can mark one of its own posts as the accepted answer. The pointer lives
in `forum_threads.solution_post_id` (nullable FK to `forum_posts`, cleared if the
post is hard-deleted), so it is the single source of truth — no denormalised flag
to drift.

```php
$thread->markSolution($post);   // dispatches SolutionMarked; idempotent
$post->isSolution();            // true
$thread->unmarkSolution();      // dispatches SolutionUnmarked with the previous post

Thread::query()->solved()->get();
Thread::query()->unsolved()->get();
```

`markSolution()` rejects a post from another thread (`SolutionPostMismatchException`).
Authorization is handled by the `markSolution` / `unmarkSolution` policy abilities:
the thread author, or any user granted the `canModerateForum` gate.

```php
if (Gate::allows('markSolution', $thread)) {
    $thread->markSolution($post);
}
```

## Search

`Thread::search(string $term)` matches thread titles and their posts' bodies and
returns **distinct** threads (one row per thread via a correlated `EXISTS`, so a
thread with many matching posts still appears once), ranked with title matches
first, then most-recently-active.

```php
Thread::query()->search('redis caching')->paginate();
```

The scope is portable: on **MySQL/MariaDB** it uses `MATCH ... AGAINST` (FULLTEXT)
and everywhere else (including the sqlite the test suite runs on) it falls back to
a `LIKE` query — same results, no configuration needed.

The FULLTEXT indexes it relies on are added by an **optional** migration,
`add_fulltext_search_indexes_to_forum`, on `forum_threads.title` and
`forum_posts.body`. That migration is a **no-op on any non-MySQL/MariaDB driver**
(so it is safe on sqlite/pgsql), and the `LIKE` fallback means search works with
or without it. Publish and run it if you are on MySQL/MariaDB and want the faster
FULLTEXT path.

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

## API

The module ships an out-of-the-box JSON REST API built on the Core **API kit**.
It is **safe by default**: nothing is registered until you opt in.

### Enabling it

The surface is gated by `forum.http.mode` (`headless` | `api` | `ui`). Set the
env var to expose it:

```dotenv
FORUM_HTTP_MODE=api
```

`headless` (the default) registers **no routes**; `api` and `ui` register the
endpoints below. The relevant `config/forum.php` block:

```php
'http' => [
    'mode' => env('FORUM_HTTP_MODE', 'headless'),
    'prefix' => 'api/forum',
    'middleware' => ['api'],           // base middleware for every route
    'auth_middleware' => ['auth'],     // appended to write routes
    'rate_limit' => '60,1',            // "maxAttempts,decayMinutes" for the forum-api throttle
],
```

Every route is throttled by the named `forum-api` limiter (keyed by user id, or
client IP for guests) and named under the `forum.api.` prefix
(e.g. `route('forum.api.threads.index')`).

### Auth & policies

Reads are **public**; writes require authentication (the `auth_middleware`) and
are authorized by the module's **Policies** via `$this->authorize()` in the
controllers — the same policies used by the Filament admin. Guests hitting a
write route get `401`; an authenticated but unauthorized user gets `403`.
Moderators (granted the `canModerateForum` gate) bypass ownership checks.

Responses use the Core envelope: `{ "data": ... }` for single resources,
`{ "data": [...], "meta": { "pagination": ... } }` for collections, and
`{ "message": ..., "errors": ... }` for failures.

### Endpoints

All paths are relative to the `api/forum` prefix.

| Method | Path | Auth | Policy | Description |
|---|---|---|---|---|
| GET | `boards` | – | – | List boards (guests see public boards only) |
| GET | `boards/{board}` | – | `viewBoard` | Show a board |
| POST | `boards` | ✓ | `createBoard` (moderator) | Create a board |
| PATCH | `boards/{board}` | ✓ | `updateBoard` (moderator) | Update a board |
| DELETE | `boards/{board}` | ✓ | `deleteBoard` (moderator) | Delete a board |
| GET | `threads` | – | – | List threads — `filter[board\|author\|solved]`, `sort=created_at\|last_post\|replies` (prefix `-` for desc), `per_page`, `page` |
| GET | `threads/search?q=` | – | – | Full-text/`LIKE` search over titles + post bodies |
| GET | `threads/{thread}` | – | `viewThread` | Show a thread (with board + root post) |
| POST | `threads` | ✓ | `createThread` (board open) | Create a thread + its root post |
| PATCH | `threads/{thread}` | ✓ | `updateThread` (author) | Update a thread title |
| DELETE | `threads/{thread}` | ✓ | `deleteThread` (author) | Delete a thread |
| POST | `threads/{thread}/solution` | ✓ | `markSolution` (author) | Mark a post (`post_id`) as the answer |
| DELETE | `threads/{thread}/solution` | ✓ | `unmarkSolution` (author) | Clear the accepted answer |
| POST | `threads/{thread}/subscribe` | ✓ | `viewThread` | Subscribe to a thread |
| DELETE | `threads/{thread}/subscribe` | ✓ | – | Unsubscribe from a thread |
| GET | `threads/{thread}/posts` | – | `viewThread` | List a thread's replies (paginated) |
| POST | `threads/{thread}/posts` | ✓ | `replyToThread` | Post a reply (`body`, optional `parent_id`) |
| PATCH | `posts/{post}` | ✓ | `editPost` (author, edit window) | Edit a reply |
| DELETE | `posts/{post}` | ✓ | `deletePost` (author) | Delete a reply |
| POST | `posts/{post}/vote` | ✓ | `votePost` | Vote (`value=up\|down`) |
| DELETE | `posts/{post}/vote` | ✓ | `votePost` | Remove your vote |

Controllers stay thin over the existing domain (`Thread::reply/markSolution/subscribe`,
`Post::vote/unvote`, …), so the same events, counters, and badge rules fire
whether an action comes through the API, Filament, or your own code.

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
