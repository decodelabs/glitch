# AGENTS Guide (DecodeLabs Package)

This file is a **bootstrap guide** for AI agents (Cursor, Codex CLI, ChatGPT) and human contributors working in this repository.

Most global rules and architectural expectations are defined centrally in the **Chorus** repository.  
This file only provides the minimal information needed to locate that guidance and apply it correctly within *this* package.

---

## 1. Locate Chorus (Required)

Before doing anything, an agent must locate the **Chorus** repository, which contains:

- global architecture & coding standards  
- the AI behaviour guide  
- development workflows  
- all templates (README, package spec, feature spec, change spec, AGENTS, etc.)  
- ecosystem metadata (`packages.json`)  
- the versioned migration pipeline  

Search for Chorus in this order:

1. **Sibling directory** (most common in development):

   ```
   ../chorus
   ```

2. **Composer dev dependency**:

   ```
   vendor/decodelabs/chorus
   ```

3. **Fallback (read-only):**

   https://github.com/decodelabs/chorus

Once located, read:

- `docs/architecture/agents-guide.md`  
- `docs/architecture/ai-integration-workflow.md`  
- `docs/architecture/coding-standards.md`  
- any templates under `docs/templates/`

These global rules override and supplement everything in this file.

---

## 2. Read Local Documentation

In this repository, always read:

- `README.md`  
- `docs/meta/spec.md` (package specification)
- any feature specs under `docs/meta/features/`
- any relevant files under `docs/`

These describe this package’s purpose, constraints, and public API.

---

## 3. Single-Repository Rule (Critical)

When acting inside this repository:

- You may **only** modify this repository.  
- You may **not** modify Chorus or any sibling package.  
- You may **not** perform multi-repo refactors.  
- You may **not** propagate behavioural changes to frameworks or client projects from here.

If a required change spans multiple repos:

- Document it in Chorus (via a Change Spec), and  
- Stop making changes in this repository until instructed.

---

## 4. Behaviour When Unsure

If anything is unclear:

- Stop and ask, **or**
- Add a safe TODO comment:
  ```php
  // TODO: clarify expected behaviour here
  ```
  ```markdown
  <!-- TODO: determine correct nullability or error-handling semantics -->
  ```

Agents must **never** invent new behaviour, APIs, configuration, or semantics not present in:

- this repository’s code/spec, or  
- Chorus documentation.

---

## 5. Summary Checklist for Agents

Before making any change:

- [ ] Found Chorus (sibling/vendor/remote)  
- [ ] Read global agent rules in `agents-guide.md`  
- [ ] Read local `README.md` and package spec  
- [ ] Confirm this is a **single-repo** change  
- [ ] Identify relevant templates from Chorus  
- [ ] Ensure SemVer, behaviour, and scope are understood  

While acting:

- [ ] Follow coding standards and architectural rules  
- [ ] Keep changes small, focused, and high quality  
- [ ] Add TODOs rather than guessing  
- [ ] Do not propagate changes outside this repo  

After acting:

- [ ] Update docs/spec/tests where appropriate  
- [ ] Ensure consistency with Chorus documentation  
- [ ] Maintain exceptional clarity and code quality  

---

This file intentionally contains **no package-specific content**.  
All cross-repo rules and architectural details live centrally in the Chorus repository.
