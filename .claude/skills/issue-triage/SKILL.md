---
name: issue-triage
description: Triage a Sensei GitHub issue and post a triage comment directly on it. Classifies the report as a bug or feature request, checks it against the Sensei support policy and existing code, reproduces bugs via the e2e-testing skill, and posts a structured verdict (scope, evidence, likely affected code, priority, effort, suggested fix). Use when asked to triage a Sensei issue or when invoked on a `claude-triage` trigger.
---

# Sensei Issue Triage

Produce a single, well-structured triage comment on a Sensei GitHub issue. This skill **triages** — it does not fix. It never opens a PR, never closes the issue, and never pushes code. The fix-and-PR flow lives in `.github/claude-system-prompt.md`; this skill stops at a comment plus labels.

## Inputs

An issue number or URL (e.g. `1234` or `https://github.com/Automattic/sensei/issues/1234`). Default repo is `Automattic/sensei`. When triggered from a GitHub event, the issue number is in the trigger context.

## Operating environments

This skill runs in one of two environments. Detect which and adapt the reproduction step:

- **Interactive (local Claude Code):** `make up` (wp-env) and Chrome DevTools MCP are available. Reproduce bugs for real using the **e2e-testing** skill (`.claude/skills/e2e-testing/SKILL.md`).
- **CI (`claude-code-action` runner):** No wp-env Docker and no browser MCP — only the PHPUnit test environment (`install-wp-tests.sh` + MySQL). You cannot drive a browser here. Confirm bugs by tracing the code path and, where practical, writing a failing PHPUnit test that captures the reported behavior. Be explicit in the comment that reproduction was code-level, not browser-level.

Never claim a browser reproduction you didn't actually run.

## Workflow

### 1. Fetch and read the issue

```bash
gh issue view <number> --repo Automattic/sensei --json number,title,body,labels,author,comments
```

Read the body and existing comments. Note the reporter's WordPress/PHP/Sensei versions, active theme, other plugins, and any screenshots or screencasts.

### 2. Classify

Decide the report type. The repo's type labels are `[Type] Bug`, `[Type] Enhancement`, `[Type] Question`, `[Type] Proposal`.

- **Bug report** → go to [Bug triage](#bug-triage).
- **Feature request / enhancement / proposal** → go to [Feature triage](#feature-triage).
- **Question / support request** (no defect, no feature ask) → briefly answer if possible, point to the [support policy](https://senseilms.com/documentation/support-policy/), and recommend the support channel rather than GitHub. Keep it short; don't run the full bug/feature flow.

If the report is ambiguous, ask the reporter to clarify rather than guessing.

### 3. Support-policy scope check (both types)

Sensei's [support policy](https://senseilms.com/documentation/support-policy/) bounds what the plugin owns. Treat as **in scope**: install/config/setup of Sensei, defects in built-in Sensei features, and behavior of Sensei's own blocks, REST endpoints, and admin screens. Treat as **out of scope** (route elsewhere, don't fix as a core bug):

- Third-party plugin or theme **conflicts** — ask the reporter to reproduce with a default theme (e.g. `course` / a core theme) and only Sensei active. Apply the `Third-Party` label when a conflict is the likely cause.
- **Customizations / custom code** — anything changing how Sensei looks or functions via snippets, child themes, or page builders.
- **Cosmetic / CSS / design** changes driven by the active theme.
- Server, hosting, database, or environment troubleshooting.
- Unsupported/legacy WordPress or Sensei versions (Sensei supports roughly the latest two WordPress releases).

State the scope conclusion in the comment. If clearly out of scope, say so plainly and stop before the reproduce/fix steps.

### Sensei Pro (separate plugin)

This repo is **Sensei LMS** (the free core plugin). **Sensei Pro** is a separate plugin maintained in a private repository, and its blocks/features use the `sensei-pro/*` namespace (e.g. `sensei-pro/task-list`), live under `sensei-pro/` slugs, or reference Pro-only features (interactive blocks, advanced quiz, conditional content, WooCommerce paid courses, etc.). When a report is about a Sensei Pro feature or bug:

1. **Check whether the fix can land in Sensei core.** Search this repo — the trigger may be core code, a core hook/filter, or shared markup that core also emits. If core can fully resolve it (e.g. a shared template, a hook Pro relies on, or core-owned output), triage and fix it here as normal.
2. **If it can only be handled in Sensei Pro, do not attempt a core fix or post core triage detail publicly.** Instead, submit the report into the private Sensei Pro repo on the user's behalf (next section), then post the short public [hand-off comment](#sensei-pro-hand-off-template). Do not apply a `[Pri]` label on the public issue. The Sensei Pro repository is private and **only staff can access it**, so never ask the reporter to move or re-file it there themselves.

#### Auto-submit into Sensei Pro on the user's behalf

The private repo is `Automattic/sensei-pro`. **This path is for tooling only — never write it, or any private analysis, into a public comment.**

1. **Branch on how the triage was invoked:**
   - **CI (GitHub Actions):** detect with `[ -n "$GITHUB_ACTIONS" ]`. **Do not attempt cross-repo creation** — the job's `GITHUB_TOKEN` is scoped to this repo only and can't reach the private repo. Skip straight to the public hand-off comment using the **staff-follow-up wording** ("A staff member will review this and submit an internal Sensei Pro request on your behalf"), **leave `[Status] Needs Triage` in place** so the issue stays in the human triage queue, and add one line to your run summary that a staff member must file the Pro issue. Then stop.
   - **Interactive (a staff member running locally):** confirm access with `gh repo view Automattic/sensei-pro --json viewerPermission`. If it succeeds, **proceed with cross-repo creation** (steps 2–5). If it unexpectedly fails (staff without Pro access), fall back to the same staff-follow-up behavior as the CI branch.
2. **Build the internal issue.** Unlike the public comment, the private issue **should carry the full triage** — it's staff-only. Include:
   - A first line attributing it: `_Submitted by staff on behalf of [reporter] who filed Automattic/sensei#<N>. Triage assisted by Claude._`
   - The user's request/repro verbatim or summarized, your scope analysis, and (for bugs) repro outcome, likely affected area, priority, and effort.
   - Any support-ticket reference formatted as `<number>-zen` (e.g. `11270346-zen`) — internal issue only, never the public comment.
   - A link back to the public issue: `Automattic/sensei#<N>`.
3. **Create it:**
   ```bash
   gh issue create --repo Automattic/sensei-pro \
     --title "<concise title> (from Sensei LMS #<N>)" \
     --body-file /tmp/pro-issue.md \
     --label "<[Type] Bug | [Type] Enhancement>" \
     --label "[Status] Needs Triage" \
     --label "Customer Report"
   ```
   Use `[Type] Bug` for bugs, `[Type] Enhancement` for feature requests. Always add `Customer Report` (it originates from a user) and `[Status] Needs Triage`.
4. **Capture the new issue URL** but keep it private — it goes in your run summary, never in a public comment.
5. **Then** post the public [hand-off comment](#sensei-pro-hand-off-template) and apply the public labels (`[Type] Bug`/`[Type] Enhancement`, remove `[Status] Needs Triage`).

**Never reveal the private repo or its code.** Sensei Pro (and any other Automattic-private repo) is closed-source, and issues on this repo are public. In any **public** comment: do not name the private repository, do not paste its issue links, and do not quote, reconstruct, paraphrase, or describe its source, file paths, function names, or internal behavior. The public output is limited to "this needs to be handled in Sensei Pro, and staff have submitted an internal request on the user's behalf." If you have private source in context, treat it as entirely off-limits for anything posted publicly.

---

## Bug triage

Follow these in order. Stop early (and say where you stopped) if a gate fails.

### B1. Scope

Apply the [scope check](#3-support-policy-scope-check-both-types). If the symptom points at a theme/plugin conflict or a customization, frame the comment around isolating that, request a clean-environment repro, and don't proceed to a code-level fix.

### B2. Reproducible-steps completeness

A triagable bug needs, per the [bug-report guidelines](https://senseilms.com/documentation/contribute/#submit-a-bug-report-on-github):

1. Confirmation the reporter checked for plugin/theme conflicts.
2. Up-to-date WordPress core, Sensei, extensions, and other plugins/themes.
3. Whether it's browser-specific.
4. Which users it affects (one user / multiple / admins only).
5. Evidence: screenshots and/or a screencast.
6. Environment details (WP Admin → Tools → Site Health → Info).
7. Numbered, deterministic steps to reproduce, plus expected vs actual result.

If steps are missing or non-deterministic, **do not guess**. Post a comment asking the reporter to resubmit per the guideline above, apply `[Status] Needs Author Reply`, and stop.

### B3. Reproduce

- **Interactive:** invoke the **e2e-testing** skill. Scope from the reported steps, seed the minimal data it describes, drive the relevant Sensei surface, capture screenshots, and watch the console. Record the exact environment (WP/PHP versions, theme) and the observed outcome.
- **CI:** trace the code path from the reported entry point to the suspected defect. Where practical, write a failing PHPUnit test (`vendor/bin/phpunit --no-progress -c phpunit.xml --filter "<TestClass>"`) that captures the behavior. Do not commit it — it's evidence for the triage, not a PR.

Classify the outcome: **Reproduced**, **Could not reproduce**, or **Inconclusive** (e.g. environment-dependent).

### B4. Suggested fix

When reproduced (or confidently traced), include:

- **Priority** — map impact to the repo's labels:
  - `[Pri] Critical` — data loss, security, or a broken core flow for all users; ship same day.
  - `[Pri] High` — significant breakage with no easy workaround; ship ASAP.
  - `[Pri] Normal` — real bug with a workaround; can wait for the next release.
  - `[Pri] Low` — cosmetic / low impact / easy workaround.
  - Weigh reach (how many users) × severity (data/blocking vs. cosmetic). Note `Popular Request` / repeated reports if visible.
- **Estimated effort** — High / Mid / Low, with one line of rationale (surface area touched, test complexity, HPPS/migration concerns).
- **Likely affected code** — concrete `path/to/file.php:line` references with a one-line note on each.
- **Suggested fix** — the minimal change that addresses the root cause, not the symptom.

Then post the [bug comment](#bug-comment-template), apply `[Type] Bug` + the priority label + relevant area label(s), swap `[Status] Needs Triage` for `[Status] Triaged`.

---

## Feature triage

### F1. Does it already exist?

Search the codebase before recommending anything. The feature, or a close equivalent, may already ship — check settings, blocks, hooks/filters, and admin screens.

```bash
git grep -ni "<feature keyword>" -- includes/ assets/
```

If it already exists, point the reporter to it (setting path, block name, or hook) and recommend closing as already-supported.

### F2. Scope against the support policy

Decide whether the request belongs in Sensei core or is better served by a **custom plugin/theme**. Per the [support policy](https://senseilms.com/documentation/support-policy/), site-specific behavior, cosmetic/design preferences, and niche workflow changes are typically out of core scope and better as customization. General, broadly useful capabilities that fit Sensei's LMS mission are candidates for core.

If out of core scope, say so and suggest the customization route (relevant hooks/filters if they exist, or a small companion plugin).

### F3. Priority / demand

Gauge demand: 👍 reactions, linked/duplicate issues, repeated requests, `Popular Request` signals. Recommend a priority/type label (`[Type] Enhancement` or `[Type] Proposal`; `Popular Request` when warranted). Enhancements are rarely `[Pri] High` unless they unblock a core flow.

Then post the [feature comment](#feature-comment-template) and apply the type + area + (if applicable) `Popular Request` labels; swap `[Status] Needs Triage` for `[Status] Triaged`.

---

## Posting the comment

Write the comment body to a temp file and post it (avoids shell-quoting issues with markdown):

```bash
gh issue comment <number> --repo Automattic/sensei --body-file /tmp/triage.md
```

Apply labels with `gh issue edit <number> --repo Automattic/sensei --add-label "<label>" --remove-label "[Status] Needs Triage"`.

**Post exactly one triage comment.** Keep it skimmable: a one-line verdict up top, details in `<details>`, concrete file references, and an explicit priority/effort line for bugs.

### Conventions (apply to every comment and every internal hand-off issue)

- **Attribution.** End every public triage comment, and every internal Sensei Pro hand-off issue body, with a final line: `_Triage assisted by Claude._`
- **User ticket references.** Format support-ticket references as `<number>-zen` (e.g. `11270346-zen`), not "Zendesk ticket 11270346". These belong only in the **internal** Sensei Pro issue — never put a ticket reference in a public comment.

### Bug comment template

```markdown
## Triage Results: <✅ Reproduced | ❓ Could Not Reproduce | ⚠️ Inconclusive | 🚫 Out of Scope | 🔁 Needs More Info>

<One- or two-sentence summary of the verdict.>

<details>
<summary>Reproduction Workflow</summary>

**Environment:** WP <ver>, PHP <ver>, theme <name>, Sensei <ver> (browser repro via e2e-testing skill | code-level trace in CI)

<Numbered steps taken and what was observed at each.>

</details>

### 📸 Screenshots
<Embedded images or "Captured under `.claude/tmp/screenshots/` — see Actions artifacts." Omit this section in CI if none.>

### Scope assessment
<In scope for Sensei core | Likely theme/plugin conflict | Customization — with one line of reasoning.>

### Evidence
<What confirms the bug: the failing path, console error, or failing test output.>

### Likely affected code
- `includes/.../file.php:NN` — <what this line does and why it's implicated>

### Suggested fix
**Priority:** `[Pri] <Critical|High|Normal|Low>` — <impact: reach × severity>
**Estimated effort:** <High|Mid|Low> — <one-line rationale>

<The minimal root-cause fix. Reference the lines above.>

_Triage assisted by Claude._
```

For **Needs More Info**, drop Evidence/Affected-code/Suggested-fix and instead list exactly which items from the [bug-report guidelines](https://senseilms.com/documentation/contribute/#submit-a-bug-report-on-github) are missing.

### Sensei Pro hand-off template

Use this — and nothing more — when the issue can only be handled in Sensei Pro. Do not add reproduction, evidence, affected code, or a suggested fix, and do not name the private repository.

```markdown
## Triage Results: 📦 Handled in Sensei Pro

This concerns Sensei Pro functionality, which is maintained separately and isn't part of Sensei LMS (core), so it can't be addressed here. **We've submitted an internal Sensei Pro request on your behalf** so the Sensei Pro team can pick it up — no further action needed from you. 🙏

_Triage assisted by Claude._
```

When auto-submit was **skipped** (CI, or staff without Pro access), change "We've submitted an internal Sensei Pro request on your behalf" to "A staff member will review this and submit an internal Sensei Pro request on your behalf."

Labels:
- **Auto-submit succeeded (interactive):** apply `[Type] Bug` (or `[Type] Enhancement`) and remove `[Status] Needs Triage`.
- **Auto-submit skipped (CI / no access):** apply `[Type] Bug` (or `[Type] Enhancement`) but **keep `[Status] Needs Triage`** so a staff member still picks it up.

In neither case add `[Pri]` or `[Status] Triaged`.

### Feature comment template

```markdown
## Triage Results: 💡 Feature Request

<One-sentence verdict: already supported / in scope / better as customization.>

### Already supported?
<Yes — point to the setting/block/hook with a path. | No — not currently in Sensei.>

### Support-policy scope
<Fits Sensei core because… | Better served by a custom plugin/theme because… (link hooks/filters if relevant).>

### Demand & priority
<Reactions, duplicate/linked issues, repeated requests. Recommended labels.>

### Recommendation
<Accept as enhancement / proposal | Decline as out of scope, suggest customization | Close as already-supported.>

_Triage assisted by Claude._
```

## Guardrails

- **Never expose the private repo or its code/analysis.** Comments on this repo are public. For a Sensei Pro issue, post only the [hand-off comment](#sensei-pro-hand-off-template) — do not name the private repository, and do not quote, paste, reconstruct, paraphrase, or describe its source, paths, function names, or internal behavior, even if that source is in your context. Never ask the reporter to move or re-file the issue into the private repo; only staff can access it, so the hand-off says staff will submit an internal Sensei Pro feature request on the user's behalf.
- One comment per run. Don't re-triage an issue already labeled `[Status] Triaged` unless asked.
- Never close issues, never `gh label delete`, never push branches or open PRs from this skill.
- Be honest about reproduction: distinguish a real browser repro from a code-level trace, and "could not reproduce" from "did not try."
- When out of scope or under-specified, route the reporter (support channel, clean-env repro, resubmit) instead of forcing a fix.
- Keep tool output small in CI (single pipes, `--json` field selection) — the action runs with limited turns.
