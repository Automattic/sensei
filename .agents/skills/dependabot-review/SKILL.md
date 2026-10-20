---
name: dependabot-review
description: >-
  Review and test a user-provided Dependabot pull request in the Sensei
  repository. Use when deciding whether its updated dependency is still needed,
  selecting focused validation for its actual consumer, documenting merge
  safety, or replacing an obsolete dependency update with a separate removal
  pull request.
---

# Review a Sensei Dependabot Pull Request

Review a dependency update, test the affected functionality, and provide an
evidence-based recommendation on whether it is safe to merge. Do not assume
that a declared dependency is used, that a green install proves compatibility,
or that every update needs browser testing.

The user must provide one Dependabot PR URL or PR number. Review only that PR.
If no PR is provided, ask for one instead of searching for or selecting an open
Dependabot PR.

A request to review or test a specific Dependabot PR authorizes read-only PR
inspection, a safe local checkout, dependency installation, focused tests, and
one final review comment. It also authorizes the removal workflow in section 3
when the dependency is demonstrably obsolete. Explicit limits such as
"analysis only", "do not comment", or "do not open a PR" take precedence. Do
not merge a PR or modify the Dependabot branch unless separately requested.

## 1. Establish the exact change

Confirm GitHub authentication as described in `AGENTS.md`, then inspect the
provided PR, its diff, commits, current head SHA, existing discussion, and
current checks.
Verify that the author is Dependabot and that the base is `trunk`; if either is
different, explain the mismatch before applying this workflow.

Preserve the user's working tree. Reuse a clean checkout already on the PR head,
or use an isolated worktree when switching would disturb local work. Record the
head SHA used for testing. If Dependabot advances the branch later, reassess the
new diff and repeat any affected checks before commenting.

Identify every changed package and classify it separately:

- direct production dependency;
- direct development or build dependency;
- peer dependency the project must provide;
- transitive dependency owned by another package; or
- GitHub Action or other workflow-only dependency.

Do not describe a grouped update as one homogeneous change when its packages
have different consumers or risks.

## 2. Determine whether Sensei still needs it

Do this before choosing tests. Trace actual ownership rather than relying only
on the manifest entry:

- Search imports, `require` calls, PHP symbols, configuration, scripts, CLI
  commands, workflow files, generated-asset code, and packaging hooks.
- Use the package manager's graph tools where applicable, such as `npm explain`,
  `npm ls --all`, `composer why`, and `composer why-not`.
- Inspect `peerDependencies`, optional peers, executable package contents, and
  the package's role in any consuming tool. A package can be required without a
  direct source import.
- Trace the declaration's introduction and the later history of its original
  feature with `git log -S`, `git blame`, and the associated PRs when present.
- Distinguish a package that merely remains installed transitively from a peer
  or direct dependency that Sensei is responsible for declaring.

Treat the dependency as removable only when the evidence covers source,
configuration/tooling, dependency ownership, and relevant history. Do not
remove a transitive package directly when its parent is still used.

## 3. If the dependency is obsolete

Do not turn the Dependabot update branch into a removal. Create a separate
cleanup branch from a freshly fetched `origin/trunk`, never with a `codex/`
prefix. Remove the direct declaration, regenerate only the applicable lockfile,
and confirm that no unrelated dependency or metadata churn entered the diff.

Validate the removal proportionately: perform a clean install, run the smallest
consumer/build check that could expose a missing dependency, inspect the final
dependency graph, and run `git diff --check`. Follow `AGENTS.md`; leave broad
linting, full suites, and compatibility matrices to CI.

Read and follow the repository's `pull-request` skill to commit, push, and open
the separate cleanup PR. This is internal maintenance, so use `No Changelog`
unless the removal changes user-visible behavior. Wait until the new PR exists
and its URL is verified before closing the Dependabot PR.

Close the original PR with a short comment containing:

- the concrete reason Sensei no longer needs the dependency;
- the former consumer or removal/replacement history that proves it; and
- a link to the cleanup PR.

If the cleanup PR cannot be created, do not close the Dependabot PR; report the
blocker instead.

## 4. If the dependency is used

Inspect the upstream changelog, release notes, engine requirements, peer ranges,
package exports, migration notes, and security impact for the exact old and new
versions. For WordPress packages or ambiguous releases, inspect the published
package contents rather than treating a broad monorepo comparison as the package
diff. Check comparable Automattic repositories when their integration choices
would materially reduce uncertainty, and label inference as inference.

Install the PR's exact locked dependency graph using the repository's pinned
runtime:

- npm: run `nvm use`, then `npm ci`;
- Composer: use the wp-env-backed `make install-php` flow;
- GitHub Actions: there is no local package install, so inspect the changed
  action usage and validate through the applicable workflow run.

An install failure, peer conflict, unsupported engine, module-load error, or
runtime fatal is a compatibility result, not a reason to use `--force`,
`--legacy-peer-deps`, or another bypass.

Read [testing strategies](references/testing-strategies.md) and run only the
sections matching the dependency's real consumers. Prefer focused tests and
observable output over broad regression suites. For a visible or interactive
surface, also use the repository's `e2e-testing` skill and verify the affected
flow in the browser. Explain any testing that cannot be performed.

## 5. Decide merge readiness from current evidence

Refresh checks for the tested head SHA after local testing. An empty, queued, or
pending check list is not a pass. Inspect logs for failures and distinguish:

- a deterministic dependency regression;
- unrelated existing failure;
- transient runner, network, or registry failure; and
- expected workflow skips.

Do not push a fix to the Dependabot branch without separate authorization. Do
not claim the update is safe while a relevant concern is unresolved or required
CI is still pending. If the branch changes after testing, verify the new head
before relying on earlier results.

Use these recommendation thresholds:

- **Safe to merge:** the dependency is used, the install and focused validation
  passed, required current-head CI passed, and no material concern remains.
- **Not safe to merge:** a reproducible compatibility or product regression is
  unresolved. State what failed and the practical impact.
- **Not ready yet:** testing is incomplete or relevant CI is pending, stuck, or
  unavailable. State exactly what remains rather than predicting success.

## 6. Leave one concise PR comment

Post or update one plain-language summary after the review reaches a supported
conclusion. Avoid a raw command transcript and unexplained package jargon.
Connect each check to what it proves.

Use this shape, omitting empty sections:

```markdown
Tested:
- <check and the behavior/output it verified>

Concerns:
- <specific concern and impact>

Recommendation: Safe to merge.
```

Use exactly one of `Safe to merge`, `Not safe to merge`, or `Not ready yet`,
followed by a short reason when useful. Mention relevant CI coverage, but do not
list every routine check. If a prior skill comment exists, update it rather than
adding a duplicate.

Return the PR URL, tested head SHA, checks performed, conclusion, and any
remaining blocker to the user.
