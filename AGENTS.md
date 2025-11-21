# AGENTS Guide

This document is for **tools and AI agents** (e.g. Cursor, Codex CLI, ChatGPT) and human contributors working in this repository.

This package is part of the **Decode Labs** ecosystem.  
Global architecture, coding standards, and documentation templates are defined in the **Chorus** repository.

Your primary goals when editing this repository are:

- Maintain **exceptional quality**.
- Preserve **clarity, small responsibilities, and good documentation**.
- Avoid **wide-ranging, cross-repository changes**.

---

## 1. Where to Find Chorus

Chorus is the **meta / architecture** repository for Decode Labs.

When working in this repository, look for Chorus in this order:

1. **Sibling directory** (preferred, usually in local development):

   ```text
   ../chorus
   ```

2. **Composer dev dependency** (if installed):

   ```text
   vendor/decodelabs/chorus
   ```

3. **Remote repository** (read-only fallback):

   ```text
   https://github.com/decodelabs/chorus
   ```

Chorus is updated frequently. Always prefer a **local clone** (sibling directory or vendor copy) over remote browsing where possible.

---

## 2. What to Read Before Changing Code

Before making any changes in this repository, an agent should read:

### 2.1 In This Repository

- `AGENTS.md` (this file).
- `README.md`.
- `docs/meta/spec.md` (package specification, if present).
- Any local `CONTRIBUTING.md` or relevant files under `docs/`.

These files explain **what this package is for**, its **public surface**, and any **package-specific constraints**.

### 2.2 In Chorus

In the Chorus repository (local or remote), read:

- `docs/architecture/principles.md`  
  Overall architectural philosophy and design rules.

- `docs/architecture/package-taxonomy.md`  
  How packages are grouped and where this repository fits conceptually.

- `docs/architecture/coding-standards.md`  
  Global coding standards for PHP (including method signatures, nullable return conventions, properties vs getters, etc.).

- Templates under `docs/templates/` (if present), for example:
  - `docs/templates/README.md`
  - `docs/templates/package-spec.md`
  - `docs/templates/AGENTS.md`

> **Note:** Some templates may not exist yet.  
> When they do exist, they should be treated as the **canonical shape** for new or updated documentation.  
> When they do not, infer style and structure from existing Decode Labs repositories and documentation.

---

## 3. Quality Expectations

Decode Labs libraries are **public-facing** and held to a **very high quality bar**.

When generating or editing code:

- Keep responsibilities **small and focused**.
- Aim for **clear, self-documenting APIs** with consistent naming.
- Avoid **code smells** (large classes, deeply nested conditionals, magic numbers, hidden side effects, etc.).
- Avoid **security issues** (unvalidated input, unsafe evals, naive crypto, etc.).
- Preserve or improve:
  - **Test coverage**, and
  - **Documentation quality**.

Where possible:

- Respect the project’s existing tooling:
  - `php-cs-fixer` configuration (baseline: `@PSR12,-method_argument_space,array_syntax`).
  - Static analysis tools (PHPStan/Psalm) if configured.
  - Test runner (phpunit/pest/etc.).
- Do not introduce patterns that conflict with the principles and standards in Chorus.

---

## 4. Coding Standards (Summary)

This repository follows the **Decode Labs coding standards** as defined in Chorus.

Highlights (non-exhaustive; see Chorus for full details):

- **Method signatures:**
  - Parameters on separate lines when parameters exist:

    ```php
    public function doThing(
        string $name,
        int $count,
    ): void {
        // ...
    }
    ```

  - No parameters → no extra lines, brace on its own line:

    ```php
    public function reset(): void
    {
        // ...
    }
    ```

- **Nullable returns and `try*` methods:**
  - A method that returns `?Type` because the value “may not exist” should generally be named `trySomething()`.
  - A corresponding non-nullable `something()` method should exist that:
    - calls `trySomething()`, and
    - throws a domain-appropriate exception when no value is available.
  - Apply common sense; not every nullable return needs a `try*` pair, but this is the default pattern.

- **Properties over getters/setters:**
  - For simple data, prefer properties (especially readonly) over Java-style `getX()` / `setX()` instance methods.
  - Static methods remain acceptable where they act as factories or lookups, but should still be named as verbs.

- **Method names as verbs:**
  - Methods should be named as verbs or verb phrases (`handleRequest()`, `buildResponse()`, `loadConfig()`), not bare nouns.

For full details and examples, see:

- `docs/architecture/coding-standards.md` in Chorus.

---

## 5. Scope of Changes

Agents must keep changes **scoped to a single repository at a time**.

- Do **not** perform multi-repository refactors or sweeping changes.
- Do **not** open or edit sibling repositories automatically.
- If a change logically spans multiple packages:
  - Document the need (e.g. in comments, commit messages, or a short note in the spec).
  - Leave cross-repo coordination to human architects (who will drive those changes with separate, repo-specific prompts).

Within this repository:

- Prefer small, focused pull requests / change sets.
- Avoid unnecessary renaming or reformatting that obscures the behavioural change.

---

## 6. Behaviour When Unsure

If you are uncertain about:

- the intended behaviour of a function or class,
- whether a change may break backwards compatibility,
- or how a global rule from Chorus should apply here,

then:

1. Prefer **not** to make the change, or
2. Make a minimal change and add a clear note, for example:

   ```php
   // TODO: clarify whether this should throw or return null in this case.
   ```

or in documentation:

```markdown
<!-- TODO: clarify the intended error-handling behaviour for this operation. -->
```

Do **not** invent behaviour, API endpoints, or configuration options that are not present in the code or documented in Chorus.

---

## 7. Documentation and Templates

When creating or updating documentation in this repository:

- Prefer using templates from Chorus when available (e.g. README, package spec, AGENTS).
- Match:
  - tone,
  - structure,
  - level of detail
seen in other Decode Labs packages.

If a suitable template does not yet exist:

- Follow the structure already present in this repository or other Decode Labs repos.
- Keep documentation:
  - **Concise but clear**,
  - **Task-oriented** (how to use, what it does, what it does *not* do),
  - **Aligned** with the architecture principles in Chorus.

---

## 8. Summary for Agents

Before you change anything in this repository:

1. Locate and read Chorus (sibling `chorus/`, `vendor/decodelabs/chorus`, or remote).
2. Read:
   - Chorus architecture principles,
   - Coding standards,
   - Package taxonomy.
3. Read this repository’s:
   - `AGENTS.md`,
   - `README.md`,
   - `docs/meta/spec.md`.
4. Make **small, scoped, high-quality changes** that:
   - respect existing architecture,
   - follow the global coding standards,
   - and keep tests and docs in good shape.
5. Do not perform multi-repo refactors.  
   If a wider change is needed, leave a note for human architects.

If in doubt, stop and ask for clarification rather than guessing.
