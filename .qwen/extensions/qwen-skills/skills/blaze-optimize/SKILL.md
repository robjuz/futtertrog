---
name: blaze-optimize
description: "Set up and optimize Blade component rendering with Blaze. Use when installing Blaze, optimizing components, or configuring @blaze directives and strategies."
---

# Blaze Optimize

Blaze compiles anonymous Blade components into optimized PHP functions, significantly reducing rendering overhead.

## EXECUTE Steps 1-5 Immediately

Do NOT prompt for confirmation or offer choices — these are safe, low-risk changes. Only pause at step 6 to present the summary.

### 1. Install

Check `composer.json` for `livewire/blaze`. If missing:

```bash
composer require livewire/blaze:^1.0
```

### 2. Codebase Scan

Quick scan before making changes. Do not read individual component files.

Check:
- `composer.json` for `livewire/flux`, `livewire/flux-pro`, `livewire/livewire`, `livewire/blaze`
- Component count: glob `resources/views/components/**/*.blade.php`
- Directory structure under `resources/views/components/`
- Custom component paths: grep providers for `anonymousComponentPath` or `anonymousComponentNamespace` — these register additional component directories that also need optimization
- `app/Providers/AppServiceProvider.php` for existing `Blaze::optimize()` calls
- `.env` for `BLAZE_ENABLED` or `BLAZE_DEBUG`

### 3. Compatibility Check

Before enabling Blaze for a directory, grep its components for patterns Blaze does not support. Exclude any components or directories that match:

| Pattern | What to grep for | Why |
|---------|-----------------|-----|
| Class-based components | PHP class files in `app/View/Components/` that correspond to component directories | Blaze only supports anonymous components |
| `$component` variable | `$component` | Not available in Blaze |
| View composers/creators | `View::composer(` or `View::creator(` in `app/Providers/` targeting component views | These do not fire for Blaze components |
| `View::share()` variables | `View::share(` in providers, then grep components for those variable names | Shared variables are not auto-injected — must use `$__env->shared('key')` instead |
| Rendered via `view()` | `view('components.` or `View::make('components.` | Blaze components can only be rendered using component tags (`<x-...>`) |

### 4. Enable Compilation

Add to `AppServiceProvider::boot()`:

```php
use Livewire\Blaze\Blaze;

public function boot(): void
{
    Blaze::optimize()->in(resource_path('views/components'));
}
```

Include any custom component paths found. Exclude directories flagged in compatibility check:

```php
Blaze::optimize()
    ->in(resource_path('views/components'))
    ->in(resource_path('views/components/legacy'), compile: false)                    // directory
    ->in(resource_path('views/components/complex-widget.blade.php'), compile: false); // single file
```

### 5. Enable Debug + Clear Views

Set `BLAZE_DEBUG=true` in `.env` so trace data is collected when the user browses pages. Then clear cached views:

```bash
php artisan view:clear
```

### 6. Summary Report

Keep the summary SHORT and concise. A few bullet points.

- Compilation enabled for `resources/views/components` (72 components)
- No incompatible components found
- `BLAZE_DEBUG=true` set in `.env`

If components were excluded, list them briefly.

> **Want to go further?**
>
> **Option 1 — Trace-based optimization (RECOMMENDED)**
> Browse your app with debug mode enabled (already active), make sure to visit the **slow or component-heavy pages**, then tell me you're done. I'll analyze the trace data and apply targeted memo/fold optimizations to the slowest components.
>
> **Option 2 — Code analysis**
> Full codebase audit without trace data. **WARNING:** significantly more **token-intensive**, results are **speculative**, and incorrect optimizations can break components. Requires thorough testing.
>
> Reply **1** or **2** when ready (or skip if you don't need advanced optimization).

## STOP

Do NOT proceed with advanced optimization (memoization, folding) unless the user explicitly asks for it.

## Folding Safety Checklist

Before marking ANY component with `@blaze(fold: true)`, verify ALL of the following:

### 1. No global state access

The component must NOT internally access any of these. Search the component file for these patterns:

| Category | Patterns to search for |
|----------|----------------------|
| Database | `::where(`, `::find(`, `::get()`, `::all()`, `::first()`, `::count()`, `DB::`, `->get()` on query builders |
| Auth | `auth()`, `auth()->`, `@auth`, `@guest`, `Auth::`, `$user` (if from auth) |
| Session | `session(`, `session()->`, `Session::` |
| Request | `request()`, `request()->`, `Request::`, `$request` |
| Validation | `$errors`, `@error` |
| Time | `now()`, `Carbon::`, `today()`, `time()` |
| CSRF | `@csrf`, `csrf_token()`, `csrf_field()` |
| URL state | `url()->current()`, `url()->previous()`, `request()->path()`, `request()->is(` |
| Config/env | `config(`, `env(` (if the value might change between requests) |
| Cache | `Cache::`, `cache(` |
| App state | `app()->`, `resolve(`, `App::` |

**If ANY of these are found inside the component, do NOT fold it.**

Exception: Use `@unblaze` to exclude small stateful sections:

```blade
@unblaze(scope: ['name' => $name])
    @error($scope['name'])
        <p class="text-red-500">{{ $message }}</p>
    @enderror
@endunblaze
```

### 2. Props analysis

| Type | Safe to fold? |
|------|---------------|
| Static props (literal strings/numbers) | Yes |
| Dynamic pass-through props (output directly) | Yes, if marked `safe` |
| Dynamic non-pass-through props (used in match/if/switch) | No — cannot fold |

### 3. Slot analysis

| Slot type | Safe? |
|-----------|-------|
| Default slot (just output `{{ $slot }}`) | Yes — pass-through by default |
| Slots inspected (`$slot->hasActualContent()`) | Mark `unsafe` |
| Named slots used in logic | Mark `unsafe` |

### 4. Attribute bag analysis

If the component reads attributes not defined in `@props` via `$attributes->get()` and uses the value in logic, mark `attributes` as unsafe.

## Safe/Unsafe Parameter Reference

| Value | What it targets |
|-------|-----------------|
| `*` | All props, attributes, and slots |
| `slot` | The default slot |
| `[name]` | A specific prop, attribute, or named slot |
| `attributes` | All attributes not defined in `@props` |

- **`safe`** — Tells Blaze that these `@props` values are pass-through, allowing folding to proceed even when they receive dynamic values.
- **`unsafe`** — Tells Blaze that these values are NOT pass-through, forcing folding to abort when they receive dynamic values.

## Advanced Optimization (after user explicitly asks)

### Memoization

Use when: has no slots, has no global state access, is rendered many times with the same props (icons, avatars, badges).

```blade
@blaze(memo: true)
```

Or via service provider for entire directories:

```php
Blaze::optimize()
    ->in(resource_path('views/components'))
    ->in(resource_path('views/components/icons'), memo: true);
```

### Folding

Use when: no global state access, rendered frequently especially in loops.

```blade
@blaze(fold: true)
@blaze(fold: true, safe: ['level'])
@blaze(fold: true, unsafe: ['slot'])
@blaze(fold: true, unsafe: ['attributes'])
```

### When NOT to fold — even if technically possible

- The project doesn't have measured performance issues — premature optimization
- The component is frequently modified during development — folding requires `view:clear` after changes
- The component has complex internal logic — harder to reason about safety

## Verification

1. Run `php artisan view:clear`
2. Test the application — verify optimized components render correctly
3. If a component breaks, remove its `@blaze` directive or add `compile: false`

## Common Pitfalls

- Memoizing components that have slots — memoization requires slot-free components
- Folding components that access global state (auth, session, request, errors, time, CSRF) — produces stale HTML
- Folding components where props drive internal logic without marking them `safe`
- Forgetting to run `php artisan view:clear` after changes