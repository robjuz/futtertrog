---
name: laravel-best-practices
description: "Apply this skill whenever writing, reviewing, or refactoring Laravel PHP code. This includes creating or modifying controllers, models, migrations, form requests, policies, jobs, scheduled commands, service classes, and Eloquent queries. Triggers for N+1 and query performance issues, caching strategies, authorization and security patterns, validation, error handling, queue and job configuration, route definitions, and architectural decisions. Also use for Laravel code reviews and refactoring existing Laravel code to follow best practices. Covers any task involving Laravel backend PHP code patterns."
---

# Laravel Best Practices

Best practices for Laravel. For exact API syntax, verify with `search-docs`.

## Consistency First

Before applying any rule, check what the application already does. Laravel offers multiple valid approaches, and the best choice is the one the codebase already uses. **Inconsistency is worse than a suboptimal pattern.**

Check sibling files, related controllers, models, or tests for established patterns. If one exists, follow it. These rules are defaults for when no pattern exists, not overrides.

## How to Apply

1. Check the changed files, nearby code, project configuration, and relevant tests for established patterns. Deviate only for a correctness or security defect.
2. Map every affected concern to the rule sections below. Read each mapped rule before editing. Skip unrelated sections.
3. Make the smallest coherent change. Keep the application's architecture and naming.
4. Verify version-sensitive Laravel APIs with `search-docs`.
5. Run the narrowest relevant tests, then formatting and static-analysis checks.
6. Re-read the diff against every mapped rule before finishing.

---

## Architecture

### Extract Focused Business Operations

Extract a discrete business operation into an action class when it makes the operation easier to reuse or test. An action class has no special meaning to Laravel; follow the project's naming and invocation conventions.

```php
class CreateOrderAction
{
    public function __construct(private InventoryService $inventory) {}

    public function handle(array $data): Order
    {
        $order = Order::create($data);
        $this->inventory->reserve($order);
        return $order;
    }
}
```

### Inject Required Dependencies

Prefer constructor injection for dependencies required throughout an object's lifetime. Avoid `app()` and `resolve()` when normal injection can make a dependency explicit.

### Depend on Contracts at Boundaries

Depend on contracts at system boundaries (payment gateways, notification channels, external services) when testability or interchangeable implementations justify the abstraction. Bind in a service provider.

### Specify a Deterministic Sort Order

Without an explicit `ORDER BY`, row order is undefined. Add a unique tie-breaker when stable pagination matters.

```php
$posts = Post::query()->orderByDesc('created_at')->orderByDesc('id')->paginate();
```

### Use Atomic Locks for Race Conditions

Use `Cache::lock()` for process-level coordination or `lockForUpdate()` inside a database transaction for row-level locking. These solve different problems.

### Use `mb_*` String Functions

Prefer multibyte-aware functions (`mb_strlen()`, `mb_strtolower()`) for UTF-8 text. Or use Laravel's `Str` helpers.

### Use `defer()` for Post-Response Work

For lightweight work that does not need retries or crash durability, use `defer()` instead of dispatching a job.

```php
defer(fn () => PageView::create(['page_id' => $page->id, 'user_id' => auth()->id()]));
```

### Use `Context` for Request-Scoped Data

The `Context` facade propagates contextual data across the execution lifecycle. Visible context appears in logs; hidden context (`Context::addHidden()`) propagates to queued jobs without appearing in logs.

### Use `Concurrency::run()` for Parallel Execution

```php
use Illuminate\Support\Facades\Concurrency;

[$users, $orders] = Concurrency::run([
    fn () => User::count(),
    fn () => Order::where('status', 'pending')->count(),
]);
```

### Follow Framework Conventions

Follow Laravel conventions unless the domain or an existing schema requires an override. Do not customize table names, primary keys, or relationship definitions when defaults work.

---

## Eloquent

### Define Precise Relationship Types

Declare concrete return types on all relationships.

```php
public function comments(): HasMany { return $this->hasMany(Comment::class); }
public function author(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
```

### Use Local Scopes for Reusable Queries

Extract reusable query constraints into local scopes with the `#[Scope]` attribute.

```php
#[Scope]
protected function active(Builder $query): Builder
{
    return $query->where('verified', true)->whereNotNull('activated_at');
}
```

### Apply Global Scopes Sparingly

Global scopes silently modify every query on the model. Prefer local scopes. Reserve global scopes for truly universal constraints like soft deletes or multi-tenancy.

### Define Attribute Casts

Use the `casts()` method (or `$casts` property following project convention).

```php
protected function casts(): array
{
    return ['is_active' => 'boolean', 'metadata' => 'array', 'total' => 'decimal:2'];
}
```

### Cast Date and Time Attributes

Cast a date/timestamp attribute when application code should treat it as a Carbon instance. Avoid `Carbon::parse()` in templates.

### Use `whereBelongsTo()` for Relationship Queries

Expresses the relationship constraint without manually specifying the foreign key.

```php
Post::whereBelongsTo($user)->get();
```

### Keep Application Queries Model-Aware

Prefer Eloquent models and relationships over raw SQL for model-backed queries. They preserve casts, scopes, and table configuration. Use query builder/raw SQL only when lower-level behavior is intentional.

### Use Collections Wisely

- Use higher-order messages for simple operations: `$users->each->markAsVip()`
- Use `cursor()` for low-memory, attribute-only iteration; `lazy()` when relationships need eager loading
- Use `lazyById()` when updating records during iteration
- Use `toQuery()` for bulk operations: `$users->toQuery()->update(['active' => false])`
- Use `#[CollectedBy]` for custom collection classes

---

## Validation

### Extract Validation When It Improves the Boundary

Use a form request when validation or authorization is substantial, reused, or clearer outside the controller. Inline `$request->validate()` is fine for small, endpoint-specific rules.

```php
public function store(StorePostRequest $request): RedirectResponse
{
    $post = Post::create($request->validated());
    return redirect()->route('posts.show', $post);
}
```

### Prefer Readable Rule Syntax

Array syntax composes cleanly with rule objects. Prefer it in new code while following a consistent local style.

```php
'email' => ['required', 'email', Rule::unique('users')],
```

### Use Only Intended Validated Data

Use `validated()` or `safe()` instead of `$request->all()`. Select only the fields intended for the operation.

```php
$post = Post::create($request->safe()->only(['title', 'body']));
```

### Express Conditional Rules Clearly

Use `Rule::when()`, `required_if`, or `exclude_unless`.

### Add Cross-Field Validation After Base Rules

Use a form request's `after()` method for validation that depends on multiple fields or application state. Avoid expensive queries when prerequisite fields have already failed.

---

## Routing and Controllers

### Use Implicit Route Model Binding

Let Laravel resolve models from route parameters.

```php
public function show(Post $post): View
{
    return view('posts.show', ['post' => $post]);
}
```

### Scope Nested Bindings

Use scoped bindings when a nested resource must belong to its parent.

```php
Route::get('/users/{user}/posts/{post}', function (User $user, Post $post) {
    // The resolved post belongs to the resolved user.
})->scopeBindings();
```

### Use Resource Routes for Resourceful Actions

Use `Route::resource()` or `Route::apiResource()` when the endpoint follows standard actions. Define explicit routes for non-standard behavior.

### Organize Controllers Around Resources

Organize each controller around one resource. When a controller needs a custom action like `publish`, `approve`, or `archive`, consider whether that behavior represents a separate resource.

### Keep Controllers Focused on HTTP Concerns

Coordinate HTTP input, authorization, validation, an application operation, and the response. Extract substantial or reusable business logic. Do not introduce an action or service merely to satisfy an arbitrary line limit.

---

## Security

### Control Mass Assignment

Define `$fillable` when a model is populated from request-derived arrays, or deliberately guard attributes by another consistent model convention. `$guarded = []` opts out of protection.

Do not pass untrusted request data to a model with `$guarded = []`.

### Authorize Protected Actions

Use policies, gates, or form request authorization. Authentication alone does not establish permission, and validation is not authorization.

### Bind Query Parameters

Use Eloquent, the query builder, or explicit bindings — never interpolate untrusted values into SQL. Map user-selected identifiers to an allow-list.

### Escape Output in Its Context

Blade's `{{ }}` syntax HTML-escapes output. Use `{!! !!}` only for content sanitized for the exact HTML context.

### Apply CSRF Protection

Include `@csrf` in state-changing Blade forms in the `web` middleware. Webhooks intentionally excluded from CSRF need their own authenticity check.

### Rate Limit Sensitive Endpoints

Apply rate limits to login, password recovery, verification messages, and expensive API routes. Choose the limiter key deliberately — an IP address can unfairly group users behind shared networks.

### Validate and Store Uploads Safely

Validate content type, dimensions, and size. Use Laravel's storage methods to generate filenames and store files outside executable locations.

```php
public function rules(): array
{
    return ['avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']];
}
```

### Keep Secrets Out of Application Code

Do not commit `.env` files or hard-code credentials. Read env vars in config files, then use `config()` in application code.

### Audit Dependencies

Run `composer audit` regularly and in CI.

### Encrypt Sensitive Attributes

Use an `encrypted` cast for recoverable sensitive values and `$hidden` to omit them from JSON serialization. Encrypted values cannot be meaningfully queried and should use `TEXT` or larger columns.

---

## Migrations

### Generate Migrations with Artisan

Use `php artisan make:migration`.

### Define Foreign-Key Constraints Deliberately

Use `constrained()` when defaults match. Specify table or delete behavior when they do not.

```php
$table->foreignId('user_id')->constrained()->cascadeOnDelete();
```

### Treat Deployed Migrations as Immutable

After a migration has run in production, create a new migration for subsequent changes. Editing old files makes fresh installs differ from upgrades.

### Design Indexes for Real Queries

Add indexes based on query patterns, selectivity, and write cost. Confirm with representative data and the database's query plan.

### Stage Changes for Existing Rows

Adding a required column to a populated table needs multiple steps: add nullable, deploy code handling both states, backfill, then add constraint. Large backfills should be observable, restartable commands — not inside schema migrations.

### Make Rollbacks Honest

Implement `down()` when the change can be safely reversed. Document limitations.

---

## Database Performance

### Eager Load Relationships Before Iterating

Use `with()` to avoid N+1 queries. Constrain eager loads to select only needed columns.

```php
$users = User::with(['posts' => fn ($q) => $q->select('id', 'user_id', 'title')->latest()->limit(10)])->get();
```

### Prevent Lazy Loading in Development

```php
public function boot(): void
{
    Model::preventLazyLoading(! app()->isProduction());
}
```

### Select Only Needed Columns

Retain every key Eloquent needs for matching.

### Process Large Data Sets Incrementally

Use `chunk()`, `chunkById()`, `cursor()`, or `lazy()` depending on whether relationships need eager loading and whether updates can affect query membership.

### Add Indexes for Measured Query Patterns

Design indexes around frequent, performance-sensitive query patterns. Composite column order matters.

### Count Relationships Without Loading Them

Use `withCount()` when only counts are needed.

### Keep Queries Out of Blade Templates

Prepare data before rendering — in a controller, query service, or view composer.

---

## Error Handling

### Choose Where to Report and Render Exceptions

Follow the pattern already established by the project — exception methods or centralized callbacks in `bootstrap/app.php`.

### Mark Exceptions the Handler Should Not Report

Implement `ShouldntReport` to prevent reporting that exception type.

### Throttle High-Volume Exception Reports

Configure `throttle()` with a `Lottery` or `Limit` to sample or rate-limit reports.

### Prevent Duplicate Reports

Enable `dontReportDuplicates()` when the same exception object may pass through multiple `report()` calls.

### Define JSON Rendering for API Routes

```php
$exceptions->shouldRenderJsonWhen(fn (Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson());
```

### Add Context to Exception Classes

```php
public function context(): array { return ['order_id' => $this->orderId]; }
```

---

## Configuration

### Read Environment Variables in Configuration Files

Call `env()` only from configuration files. Application code should read through `config()`.

### Protect Production Secrets

Do not commit plaintext production secrets. Use `php artisan env:encrypt` or the platform's native secret store.

### Use `App::environment()` for Environment Checks

Correct: `if (app()->isProduction())` or `if (App::environment('production'))`

### Name Repeated Domain Values

Use an enum or class constant when a domain value is repeated. Simple literals are fine for one-offs.

---

## Blade and Views

### Use `$attributes->merge()` in Components

```blade
<div {{ $attributes->merge(['class' => 'alert alert-'.$type]) }}>
    {{ $message }}
</div>
```

### Use `@pushOnce` for Per-Component Scripts

When a component renders repeatedly, `@push` adds its script every time. Use a consistently named `@pushOnce` to add it once.

### Prefer Components for Explicit Interfaces

Use Blade components when a reusable interface benefits from explicit props, an attribute bag, or slots.

### Share Compatible View Data with View Composers

Use a view composer when centralized data is needed for named views. Avoid broad wildcards when views require different data shapes.

### Return Blade Fragments for Partial Rendering

```php
return view('dashboard', compact('users'))->fragmentIf($request->hasHeader('HX-Request'), 'user-list');
```

---

## Style and Naming

| Element | Convention | Example |
|---------|------------|---------|
| Controller | Singular resource | `ArticleController` |
| Model | Singular StudlyCase | `User` |
| Table | Plural snake_case | `article_comments` |
| Column | snake_case | `meta_title` |
| Foreign key | singular_model_id | `article_id` |
| Route name | dotted snake_case | `users.show_active` |
| Method | camelCase | `getAll` |
| Variable | camelCase | `$articlesWithAuthor` |
| View | kebab-case | `show-filtered.blade.php` |
| Config file | snake_case | `google_calendar.php` |
| Enum | Singular StudlyCase | `UserType` |

### Prefer Idiomatic Syntax

| Less idiomatic | Idiomatic |
|----------------|-----------|
| `Session::get('cart')` | `session('cart')` |
| `return Redirect::back()` | `return back()` |
| `Carbon::now()` | `now()` |
| `->orderBy('created_at', 'desc')` | `->latest()` |
| `->orderBy('created_at', 'asc')` | `->oldest()` |
| `->first()?->name` | `->value('name')` |

Use `$request->string()`, `$request->integer()`, `$request->boolean()` for typed accessors.

### Use Laravel Utilities

Prefer `Str`, `Arr`, `Number`, `Uri` utilities when they communicate intent more clearly than raw PHP equivalents.

### Write Comments That Explain Why

Prefer clear names over comments that restate what the code does. Add comments for non-obvious constraints, tradeoffs, workarounds, or external behavior.