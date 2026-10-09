---
name: pull-request
description: >-
  Open a GitHub pull request for the Sensei plugin the way this repo requires.
  Use this whenever the user wants to create, open, draft, or "put up" a PR, or
  says things like "open a PR", "create a pull request", "make a PR for this
  branch", or "PR this". It fills the repo's PULL_REQUEST_TEMPLATE.md from the
  actual diff against trunk, makes sure the branch has a changelog entry,
  assigns the required milestone, and stops for approval before pushing.
  Prefer this skill over a bare `gh pr create` so the PR doesn't fail
  `pr-validation.yml` on a missing milestone or changelog, and so the body
  matches the template reviewers expect.
---

# Open a Sensei Pull Request

Fill the repo's PR template from the diff, do the CI-required chores (changelog,
milestone) so `pr-validation.yml` passes, and stop for approval before
publishing the branch and creating the PR, unless the user has already explicitly
authorized those actions.

## Who this is for

This skill assumes **write access to `Automattic/sensei`** — it opens the PR on
that repo and assigns the milestone (needs triage/write permission). One step
doesn't apply to fork / external contributors:

- **Milestone:** external contributors can't assign one — leave it; a maintainer
  sets the milestone on their side.

Everything else (template body, testing instructions, stop-before-push) applies to
everyone.

## Base branch

The base is always `trunk`. Compare against it, not `main`.

## Steps

### 1. Preconditions

- Confirm the current branch is not `trunk`. If it is, stop — nothing to PR.
- Confirm `gh` is authenticated: `gh auth status`. If not, ask the user to run
  `! gh auth login` themselves.

### 2. Understand the change from the diff

Describe what the diff does, not how you got there — commit-by-commit narration
is noise to a reviewer.

```bash
git merge-base trunk HEAD          # fork point
git log --oneline trunk..HEAD      # commits on this branch
git diff --stat trunk...HEAD       # files touched
git diff trunk...HEAD              # the actual change — read this
```

Read the diff. From it, decide the **title** — one line, imperative, concise, no
conventional-commit prefix (no `feat:` / `fix:`). Example:
`Add course link to quiz page breadcrumb`.

### 3. Fill the repo's PR template

Do not invent your own headings. Read the template and fill it in:

```bash
cat .github/PULL_REQUEST_TEMPLATE.md
```

Fill each section from the diff. Guidance per section:

- **`Resolves #`** — fill it in as `Resolves #<n>` if the user gave an issue
  number, or one appears in the branch name or the commit messages. If there's no
  issue, **remove this line entirely** — a bare `Resolves #` is noise. Never guess
  a number.
- **`## Proposed Changes`** — the reviewer-facing summary. Lead with the
  **problem or need** and the **user impact** (new capability, bug fixed,
  performance, accessibility, behavior change or trade-off), then the approach at a
  high level. Don't re-list the modified files or narrate implementation mechanics
  — the diff already shows that; prose that just restates it wastes the reviewer's
  time. If the change is user-visible, a reader should understand *what changes for
  them* without opening the diff.
- **`## Screenshots`** — keep this section only when the change is **visual**.
  Judge that from the diff: it touches front-end/editor surfaces — `assets/`
  (JS/CSS/SCSS), block markup, `render.php`, front-end templates, or editor
  components. If it's not visual (pure PHP logic, REST, data, tooling, tests),
  **remove this whole section**. When browser tools are available, capture the
  relevant screenshots and attach them using a supported upload mechanism.
  For net-new UI with no "before" state, use a single screenshot or short video.
  If capture or upload is unavailable, leave the Before/After table for the user
  to fill and identify the missing images. Local file paths are not usable image
  links in a GitHub PR body.
- **`## Testing Instructions`** — reserve this section for manual behavior checks
  in WordPress wherever possible. Use a checkbox list (`- [ ] step`) with click
  paths and expected results. Omit the entire section for CI-only changes or
  changes with no meaningful manual behavior to test, such as documentation or
  test-only changes. Do not use file or diff review as a testing step, and do not
  add placeholder text saying no manual testing is needed. Never list automated
  suites (`make test-php`, PHPUnit, Playwright/e2e) as steps; those run in CI.
- **`## New/Updated Hooks`** — fill only if the diff adds or changes an action or
  filter; describe each and its args, and plan to add the **Hooks** label. If the
  diff touches no hooks, **remove this whole section** (heading, comment, and
  placeholder) from the body rather than leaving an empty `*`.
- **`## Deprecated Code`** — fill only if the diff deprecates something; name the
  replacement and plan to add the **Deprecation** label. If nothing is deprecated,
  **remove this whole section** from the body.

### 4. Changelog — a committed `changelog/` entry

CI (`changelogger.yml`) fails unless the PR adds a file under `changelog/` or
carries the `No Changelog` label. First check whether the branch already has an
entry:

```bash
git diff --name-only --diff-filter=A trunk...HEAD -- changelog/
```

If that lists a file, the entry already exists — note it in the summary you show
the user and move on.

- **User-facing change (no committed entry yet):** create the entry
  non-interactively and commit it (`make changelog` is the interactive
  equivalent):

  ```bash
  ./vendor/bin/changelogger add --no-interaction --significance=<patch|minor|major> --type=<type> --entry="<message>"
  ```

  Valid types are the keys under `extra.changelogger.types` in `composer.json`.
  Write the message as the user-facing outcome (one sentence — the effect, not
  the implementation). When the significance is ambiguous (e.g. a `fix/` branch
  that repairs behavior but also adds a small element), lean toward `patch` /
  `fixed` — match how the work is framed rather than over-classifying it as a
  feature.
- **Internal-only change** (refactor, test-only, tooling — no user-facing effect):
  don't add an entry and plan to apply the **`No Changelog`** label to the PR.
  Say this in the summary you show the user.

### 5. Confirm publication authorization

Show the user the proposed **title** and the **filled template body** (call out the
changelog choice and any labels: Hooks / Deprecation / No Changelog). If screenshots
are missing, remind the user to attach them. If the user has already explicitly
authorized pushing and opening the PR, proceed. Otherwise, wait for explicit
approval before pushing or creating the PR.

### 6. Create the PR

After publication is authorized, push the branch explicitly, then create the PR:

```bash
git push -u origin <branch>
gh pr create --base trunk --head <branch> --title "<title>" --body-file <body-file> --assignee "@me"
```

Save the filled template body to a temporary file with actual newlines. Using
`--head` skips `gh`'s implicit pushing or forking prompts. Capture the PR URL it
prints and its PR number.
Always assign the PR to the current authenticated GitHub user using
`--assignee "@me"`.
Apply any labels you flagged (e.g. `No Changelog`, `Hooks`, `Deprecation`):

```bash
gh pr edit <PR_NUMBER> --add-label "No Changelog"
```

### 7. Assign the milestone (required — CI fails without it)

Find the next shipping milestone (lowest-versioned open one) and assign it. Use
`sort -V` (version sort) — a plain string sort mis-orders `4.9.0` vs `4.26.2`:

```bash
next=$(gh api 'repos/Automattic/sensei/milestones?state=open' --jq '.[].title' | sort -V | head -1)
gh pr edit <PR_NUMBER> --milestone "$next"
```

### 8. Wrap up

Give the user the PR URL.
