---
name: infer-conventions
description: "Use this skill to analyze how a Laravel application is actually written and record its conventions as shared rules. Trigger when the user wants to detect, infer, document, or standardize project conventions or coding style, set up or grow project rules, resolve mixed or conflicting patterns (e.g. 'are we using Form Requests or inline validation?'), or onboard agents and teammates to 'how we do things here'. Covers: a systematic sweep of convention dimensions (validation, models, architecture, testing, frontend, database), open-ended house-pattern discovery, conflict reporting, and recording rules scoped to the right paths via the Boost `record-rule` MCP tool. Do not use for one-off code review, enforcing formatting a linter already handles, or editing rule files by hand."
---

# Infer Conventions

Learn how this application writes Laravel, then record what you learn as durable, path-scoped rules. You are documenting reality, not improving it.

## Ground Rules

- **Consistency first.** The codebase's majority style is the convention. Never judge it, never propose a "better" pattern. If the app validates inline everywhere, that is the rule, even if Form Requests would be nicer.
- **Skip what an active tool produces**, keep what a tool would fight. A Rector transformation is tooling-owned only when its package and relevant rule are installed and enabled. When the app deliberately holds a form an active tool would refactor away (e.g., legacy accessors no tool can reproduce), that is what to record.
- **Record decisions, not defaults.** A consistent pattern earns a rule only when it reflects a real choice — the app took one valid option where the framework offered others, or the pattern would surprise a competent agent. Framework defaults steer nothing: anonymous migrations, `$signature` commands, `ShouldQueue` jobs, named routes, Rule objects, and `Mail::fake()` are no-ops. Ask: without this rule, would the next agent plausibly write it differently? Only "yes" earns a rule.
- **Architecture choices are gold.** Record presence and deliberate absence: Action classes and how they are invoked (`handle` / `execute` / `__invoke`), service objects, dedicated query objects exposing `builder()`, DTOs, Form Request vs inline, events and listeners vs direct calls, domain folders.
- **Evidence or silence.** A convention needs at least 3 consistent examples and no meaningful rival to become a candidate.
- **One line per rule.** The recorded rule states the convention, nothing else. One or two imperative lines. Keep detection evidence out — no counts, file lists, or examples.

## Process

### Step 0: Orient

Read `composer.json` (installed packages tell you which checklist groups apply), the Pint/PHPStan config, project rules (`.ai/rules/index.md` if present), and map the `app/` tree. List every directory beyond Laravel's default skeleton (`Http`, `Models`, `Providers`, `Console`, `Exceptions`) — these are structural patterns: `Actions`, `Services`, `Data`, `Queries`, `Repositories`, `ViewModels`, `Enums`, `Domain`, modules.

### Step 1: Predefined Sweep

Work every applicable dimension. Give each exactly one verdict:

- **Pattern.** Clears the bar: 3+ consistent examples, rival under ~20%, reflects a real choice. Record as candidate. Cite 2-3 example files.
- **Conflict.** Both styles present in meaningful numbers. Report the split. Do NOT record a preferred winner while code remains mixed.
- **Default.** Consistent but a framework or common-practice default the agent already writes unprompted. Skip.
- **No signal.** Under the bar. Skip silently.

### Step 2: Open-Ended Pass

For every non-default `app/` directory from Step 0, confirm how the pattern is used. Then find what else makes the codebase itself: base/abstract classes most code extends, traits used everywhere, tenancy/authorization scoping, naming schemes, custom helpers. Cite files.

### Step 3: Confirm

Present every candidate in one batch. Per item: dimension, verdict, evidence (counts and files), and the exact proposed `glob` / `title` / `note`. Conflicts go to the user as questions. Default mode is confirm — record only what the user approves.

### Step 4: Record

Make one `record-rule` call per approved convention via the Boost MCP tool. Choose the most specific globs that cover the evidence:

- Models: `app/Models/**` or modular equivalent
- Controllers/routing/validation: `app/Http/**`
- Actions/Services/DTOs: `app/Actions/**`, `app/Services/**`, `app/Data/**`
- Tests: `tests/**`
- Migrations: `database/migrations/**`
- App-wide (rare): `app/**`

The recorded rule text is just the convention — one or two imperative lines.

### Step 5: Summarize

List recorded rules (file and title), conflicts deferred, notable no-signals, and remind the user to commit project rules.

## Edge Cases

- **Rules disabled or `record-rule` unavailable:** detection runs as read-only, report the full rule text for manual entry.
- **Tiny or fresh app:** most dimensions land on no-signal. Record nothing.
- **Huge app:** sample representative files, do not read everything.
- **Non-standard layout (modules, DDD):** the open-ended pass catches the layout itself as convention #1. Adapt globs to observed paths.