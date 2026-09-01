---
name: testing-best-practices
description: "Laravel test design and review. Use when selecting coverage, naming or structuring tests, choosing assertions or test data, isolating dependencies, testing HTTP or security boundaries, improving suite performance, or reviewing test value. Use framework guidance or search-docs for Pest and PHPUnit syntax."
---

# Testing Best Practices

This project uses **Pest**. Follow all guidance below. Use `search-docs` for Pest/Laravel API syntax.

## Consistency First

Read nearby tests before you choose syntax and organization. A pattern repeated throughout the project is a convention, and project conventions take precedence over this skill. Follow them and give new tests the same structure.

## What to Test

- Test **observable behavior** and application contracts. A test must pass after an implementation change if the behavior stays the same.
- Cover every **changed decision** and each applicable high-value failure mode. A decision is a branch, a validation, a calculation, or an authorization.
- Exercise declarations through behavior instead of repeating their text.
- Leave framework behavior to framework tests. Testing project configuration is not testing the framework.
- Write a **feature test first**. Write a unit test only for logic that does not use the framework.
- Write a feature test for every behavior reachable through a request.
- Judge an **architecture test** by the convention it protects, not by the rules above. An `arch()` test declares a rule for an entire directory, such as the parent class of every model, the classes that may use an enum, or the methods every factory declares. It intentionally checks declarations and fails when a new file breaks the convention.

## How to Apply

1. Read the code under test. Read the tests in the same directory. Identify every decision in the code.
2. Report each defect before you write a test.
3. Write the tests. Run the smallest set of tests that covers the change. The tests must pass.

---

## Naming and Structure

### File Layout

- Name each test file `{ClassName}Test.php`.
- Place each test file at the same relative path as the class under test. `app/Actions/DeleteTeam.php` → `tests/Unit/Actions/DeleteTeamTest.php`.
- Follow the project's convention for fixture files. If none exists, put fixtures in `tests/Fixtures/`.
- Move large literal values out of the test body and into fixture files.

### Test Function

- Use `it()` for behavior of the code, name as a verb phrase.
- Use `test()` for a declarative fact, such as a grant in a policy, the labels of an enum, or the shape of a serialized model.
- Use one Pest declaration style (`it()` or `test()`) consistently in each file.

### Naming Tests

The name is a specification. State the user-visible result and the condition that causes it.

- Name the **behavior**, not the method under test. The file name already gives the class.
- Give the exact status code in the name of an API error test.
- Do not write `Given`, `When`, or `Then` in the name.

```php
it('returns 401 when no token is provided', function () { ... });
it('does not include deployments from deleted environments', function () { ... });
it('falls back to the default region when none is configured', function () { ... });
```

Use a verb that describes a result: `returns`, `renders`, `creates`, `dispatches`, `rejects`, `forbids`, `falls back`, `does not`.

Do not write `it('works correctly')` or `it('returns data')`, or `it('handleMethod creates record')`.

### Grouping

Use `describe()` if one file covers separate actions in a lifecycle. Do not use `describe()` when the file covers one action, tests differ only in input (use a dataset), or the group adds a level but does not make the file easier to read.

---

## Assertions

### Arrange, Act, Assert

Write each test in three parts: setup, one action, and assertions. Put one blank line between them. Keep each test self-contained.

### Choosing the Correct Assertion

First identify the subject of the check, then find an assertion designed for it.

| Subject | Assertion |
|---------|-----------|
| Return value, state, or transformation | `expect()` chain |
| HTTP status, JSON, session, or Inertia | Laravel response assertion |
| State in the database | Laravel database assertion |
| Existence of a model | `assertModelExists($model)` |

Use a PHPUnit assertion only if no Pest expectation and no Laravel assertion exists for the subject.

- Assert each fact **once**. Do not assert a 200 status before `assertSee` — `assertSee` already shows the page rendered.
- Use **named response assertions** like `assertNotFound()` rather than `assertStatus(404)`.
- Use Pest's **format expectations** rather than regular expressions for email, URL, UUID, etc.

### Assert a Known Value

Write the expected value in the test, or calculate it by a **different method**. Do not calculate the expected value with the logic of the implementation.

```php
// Bad — the test calculates with the implementation logic
$expected = now()->subHours(24)->floorSeconds(30)->toJson();
expect($from)->toBe($expected);

// Good — fixed input, known value
travelTo('2025-01-01 00:00:00');
expect($from)->toBe('2024-12-31T00:00:00.000000Z');
```

### Assert the Complete Result

A status code is not the complete result of a write operation. Assert each of the following if the operation changes it:

- The response or the return value.
- The state in the database.
- The jobs and events that the operation dispatches.
- The notifications and mail that the operation sends.

On the failure path, assert that the operation makes none of these changes. A test that asserts only `assertOk()` passes even when the application saves no record.

---

## Endpoint Tests

Write a test for each applicable case:

- Missing or invalid authentication
- Different tenant, team, or organization
- Insufficient role or permission
- Route or scope constraint violation
- Validation failure
- Valid request — assert both response and persisted state

### Tenant Isolation

Assert the status code for a cross-tenant request. Use `404` rather than `403` when one tenant must not learn that another tenant's record exists.

### Testing Validation

- One test for each validation rule when each failure represents a separate contract.
- One test with an empty payload to assert several required fields together.
- Assert the text of the user-visible message.
- Use a dataset for input values that need the same setup and assertions.

### Which Layer Owns Which Case

- The rule-class test owns the matrix of values that pass and fail.
- The endpoint test proves that the endpoint applies the rule and the user receives the message.
- When both tests contain the matrix, move it to the rule-class test and retain **one case** in the endpoint test. Never remove the last case.

---

## Factories and Test Data

### Each Test Makes Its Own Data

Create mutable records inside the test that uses them. Use `beforeEach()` only for configuration that applies to every test in the file. Do not create records in it.

### Record Construction

- Use `create()` if the test needs the record in the database.
- Use `make()` only if the test does not need the database.
- Use a **named factory state** instead of a raw attribute. `User::factory()->unverified()->create()` gives meaning.
- Use `for()` or the relationship helper to declare the owner of a record.
- Use `recycle()` if several records share one parent.
- Use `sequence()` if several records need different attributes.

```php
$organization = Organization::factory()->onPlan(BillingPlan::PRO)->create();
$environment = Environment::factory()->recycle($organization)->create();
```

### Datasets

Use a dataset when the setup, test body, and assertions remain the same across input values.

Use parameterized tests for: enum cases, roles and plans, boundary values, invalid input values, and input/output pairs. Write separate tests if cases need a different setup, behavior, or assertions.

---

## Fakes, Mocks, and Determinism

Tests that depend on actual time, randomness, sleeping, or network calls can fail for unrelated reasons. Control all four.

### How to Isolate a Dependency

Identify the dependency, then choose the first applicable option:

1. Always use **framework fakes** for facades: events, queues, mail, notifications, storage, HTTP client, time, sleep.
2. Use a **developer-defined fake** if the application provides one.
3. Use a **mock** for a container-resolved contract only when the real implementation leaves the process or is nondeterministic.
4. Use the **real implementation** for everything else, including the database.

### Framework Fakes

- Create each fake inside the test that needs it. Do not create fakes in a file-level `beforeEach()`.
- Pass class names to `Event::fake()` and `Queue::fake()` when you know which classes the code dispatches.
- Write one assertion for each fake.
- Assert the data of a job or event if that data is part of the behavior.
- Use `Exceptions::fake()` to assert that the application reports the correct exception. Do not use `withoutExceptionHandling()`.

### Mocking

Use `shouldReceive()` before the action for an expectation. Use `shouldHaveReceived()` after for a spy. Import: `use function Pest\Laravel\mock;`.

### Outbound HTTP

Call `Http::preventStrayRequests()`. Fake the exact endpoint used by each test. Do not call `Http::fake()` without an endpoint.

### Time and Randomness

- Freeze the time or move the time in each test that depends on a date or timestamp.
- Use `freezeTime()`, `travelTo()`, `travel()`, `travelBack()`. Do not call `Carbon::setTestNow()`.
- Use `Sleep::fake()` instead of a real sleep.

### Database

- Run real queries against the real records. Do not mock the query builder.
- Use `LazilyRefreshDatabase` instead of `RefreshDatabase`.

---

## Security Tests

Test each security boundary where user input affects authorization, rendered output, or query construction.

- **Cross-tenant access.** Request a record of a different tenant. Use `404` rather than `403` to avoid confirming existence.
- **Each unprivileged role.** Use a dataset over the roles that the endpoint must refuse.
- **Escaping user-provided content.** Test escaping in HTML and mail. Assert that dangerous characters are escaped. Do not assert an exact entity.
- **Injection into dynamic query components.** Sort columns, filter fields, sort directions.
- **An unexpected key** in a payload or configuration array.

```php
it('escapes dangerous content in the notification', function () {
    $org = Organization::factory()->make([
        'name' => "O'Reilly <script>alert('xss')</script>",
    ]);
    $content = (new QuotaApproaching($org, 80))->toMail()->render();
    expect($content)->toContain('<script>')
        ->not->toContain("<script>alert('xss')</script>");
});
```

---

## Test Suite Performance

These settings apply to the project and CI, not individual tests.

### Test Environment

- Set `BCRYPT_ROUNDS=4` in `.env.testing` or `phpunit.xml`.
- Disable XDebug. Disable pcov unless coverage is needed.
- Disable packages that perform work on every request (Pulse, Telescope, Nightwatch).
- Use `WithCachedConfig` and `WithCachedRoutes` traits.
- Call `withoutVite()` or `withoutMix()`.

### Global Fakes (in base `Pest.php`)

- `Http::preventStrayRequests()`
- `Sleep::fake(syncWithCarbon: true)`
- `Exceptions::fake()`

### Running the Suite

- Run `vendor/bin/pest --parallel` to spread tests across CPU cores.
- Run `vendor/bin/pest --parallel --tia` to run only tests affected by recent changes.
- Run `vendor/bin/pest --profile` to list the slowest tests.

### Parallel Test Conditions

A test must meet these to pass in parallel:
- Creates each record it reads (does not read records from other tests).
- Does not depend on run order.
- Does not share a file, cache key, or queue with another test.

---

## Reviewing Tests

Check every item. A passing test may still provide no value.

### Test Value
- [ ] Each test covers observable behavior and passes after implementation change
- [ ] Each tested declaration is exercised through behavior
- [ ] Each test detects a distinct defect
- [ ] Every changed decision and high-value failure mode has coverage

### Names and Structure
- [ ] Each file has name `{ClassName}Test.php` and relative path of class under test
- [ ] Each name states a result, condition, and status code for API errors
- [ ] Each file uses one declaration style consistently

### Coverage
- [ ] HTTP tests cover authentication, authorization, role, scope, and validation
- [ ] Cross-tenant request gets a status that does not confirm existence
- [ ] Complete permission matrix is in policy tests, not controller tests
- [ ] Each validation rule has one test asserting the user-visible message
- [ ] Rendered user input and dynamic query parts have security tests

### Data and Determinism
- [ ] Each test creates its mutable records directly or through a helper
- [ ] Each `beforeEach()` holds configuration only
- [ ] Each factory state and relationship gives meaning of the data
- [ ] Time, randomness, sleep, and outbound HTTP are controlled
- [ ] Each test passes alone and in the complete suite in any order

### Assertions
- [ ] Each expected value is a known value, not calculated with implementation logic
- [ ] Write operations assert response, database state, and side effects
- [ ] Each fake has one assertion with class names
- [ ] Each `expect()` chain stays on one subject

---

## Built-in Laravel Assertions

Always prefer Laravel's built-in assertions over hand-built checks:

| Subject | Assertion |
|---------|-----------|
| Database state | `assertDatabaseHas('table', [...])` |
| Model existence | `assertModelExists($model)`, `assertSoftDeleted()` |
| JSON response | `assertJsonPath('key', 'value')` |
| Redirect | `assertRedirectToRoute('route.name')` |
| Queues | `Queue::assertPushed(Job::class)` |
| Mail | `Mail::assertSent(Mailable::class)` |
| Notifications | `Notification::assertSentTo($notifiable, Notification::class)` |
| Events | `Event::assertDispatched(Event::class)` |

A hand-built check fails with `false is not true`, identifying nothing. A framework assertion names the incorrect table, value, or response.