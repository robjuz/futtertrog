# Qwen Skills Extension

This extension contains domain-specific skills imported from `.claude/skills/`.

## Available Skills

| Skill | When to Use |
|-------|-------------|
| `testing-best-practices` | Designing Laravel tests, selecting coverage, naming, assertions, isolation |
| `laravel-best-practices` | Writing/reviewing Laravel PHP code, architecture, Eloquent, validation |
| `tailwindcss-development` | Tailwind CSS v4 styling, responsive layouts, dark mode |
| `livewire-development` | Livewire 4 components, wire: directives, reactivity |
| `fluxui-development` | Flux UI components in Livewire apps |
| `fortify-development` | Fortify authentication, 2FA, passkeys, registration |
| `socialite-development` | Socialite OAuth, third-party authentication, scopes, callbacks |
| `blaze-optimize` | Blade component optimization with Livewire Blaze |
| `infer-conventions` | Analyze project conventions and record them as rules |

## Notes

- These skills reference **Boost MCP tools** (e.g., `search-docs`, `database-schema`, `record-rule`). These are available via the Laravel Boost MCP server.
- Domain skills (Tailwind, Livewire, Flux, Fortify) are self-contained.
- Consolidated skills reference internal rule sections within the same file.