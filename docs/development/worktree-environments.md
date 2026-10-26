# Worktree WordPress environments

Each checkout has its own wp-env stack, development database, test database,
uploads, and ports. Sensei is mounted from that checkout at
`wp-content/plugins/sensei`. Multiple sessions in the same checkout share that
environment; use separate worktrees for independent development and testing.

Use Node from `.nvmrc` and start Docker Desktop. In each new checkout or worktree,
install dependencies and build Sensei's JavaScript and CSS before opening the site:

```sh
npm ci
npm run build:assets
make up
make env-status
make wp CMD="plugin list"
make test-php-filter FILTER="Sensei_Quiz_Test"
npm run test:e2e -- tests/e2e-playwright/specs/your-test.spec.ts
make down
```

Build assets in each new worktree; `make up` does not build them. Run
`npm run build:assets` again after frontend changes, or use `npm run start` to
rebuild automatically while developing.

Environment identity uses Git's worktree metadata and the private registry location.
Moving a linked checkout with `git worktree move` keeps its environment, ports, and
data; run `make up` afterward to refresh the mounts. Cleanup recognizes the moved
checkout as still registered.

New primary environments use the canonical Docker project `sensei-lms`. New linked
worktree projects include a readable label and the unchanged stable ID, such as
`sensei-dev-fix-module-order-1e3c7edc71e25742`. Linked worktrees use their branch
name, or their folder name when detached. For automatically generated numeric
worktree folders, the fallback label is `worktree`.
Names are normalized to lowercase letters, digits, and hyphens and limited to 32
characters. The ID keeps environments with the same label distinct.

Choose an optional label when first creating a linked-worktree environment
(the primary Docker project remains `sensei-lms`):

```sh
make up NAME=fix-module-order
# Equivalent:
npm run wp-env -- start --name fix-module-order
```

The label is saved once; switching branches or moving a worktree does not rename
its Docker resources or build directory. Subsequent commands need no name setting.
A conflicting `--name` fails rather than creating another environment. Existing
environments keep their original project names and config locations; choosing a
new name requires explicitly recreating the environment.

Generated wp-env configuration lives in `config/<label>/` within the environment's
storage, giving build paths such as `wp-env-fix-module-order-<cache-hash>/CLI.Dockerfile`.
The extra wp-env cache hash remains for uniqueness, but the build path no longer
uses an opaque environment ID as its project label. `make env-list` shows both the
Docker project name and the stable ID.

Startup prints the development and test URLs. Newly allocated local ports start at
9000 and are reserved across managed environments, including stopped ones. An
adopted primary keeps its existing ports, such as 8888/8889. CI starts at 8888/8889
and ignores private settings. Saved ports are reused; a conflict fails rather
than silently changing URLs.

Newly initialized development sites use `admin` / `password`, unless a private
password is set. An adopted primary retains its existing accounts and passwords.
The test database is separate: development data is never cloned into it, and
automated tests supply their own fixtures.

## Automatic cloning

The first startup installs missing PHP dependencies. A new linked-worktree
environment copies development data from a running source without requiring
configuration. Default source selection is:

1. An existing `sensei-lms` Docker Compose environment takes precedence.
2. If it is absent, linked worktrees use their repository's managed primary
   checkout environment, identified through Git's shared metadata.
3. If neither exists, the environment starts with a fresh site. The primary
   checkout never automatically clones itself.

For a new contributor, run `make up` in the primary checkout and add development
content there. Keep it running, then run `make up` in a linked worktree: the
worktree automatically copies the primary environment's database and uploads.
There is no extra registration step or source setting. The existing `sensei-lms`
project must belong to the primary checkout being started.

An existing selected source must be initialized and running. A stopped,
incomplete, or unreadable source fails rather than silently producing an empty
copy; start or finish setting up that source, or use `NO_CLONE=1 make up` to start
fresh. CI always starts fresh.

Cloning only reads the source: it never starts, stops, reconfigures, or cleans it.
Config and database commands avoid loading source WordPress hooks. Running
`make up` in the primary checkout adopts its existing `sensei-lms` project, keeping
the database volumes and ports. It preserves uploads and installed plugins in
persistent storage, retains the current local WordPress and Course directories,
and skips cloning and account initialization. Existing passwords and content
remain in place. Adoption refuses resources belonging to another checkout.
The retained WordPress and Course directories remain in use; do not delete their
original wp-env cache directory. Explicit `WP=` overrides can later select a
different WordPress version. Linked worktrees continue to use isolated
environments.

SQL streams directly between the source and target containers. Both process exit
codes are checked; no temporary SQL dump is created. URLs are replaced on the
target, and uploads are copied into the target's own storage. Cloning supports
single-site WordPress with the standard uploads directory; multisite is rejected.
Only Sensei is activated on first initialization; other plugins' data remains in
the database. Source plugin files are not copied.

Each checkout has one environment. Install and activate additional plugins
in the worktrees that need them, using the admin or `make wp CMD="..."`. Plugins
installed in one environment do not add overhead to another. Automated tests use
a separate database and may require their own integration setup and fixtures.

Installed plugins have separate persistent directories for development and tests.
Sensei remains mounted live from the worktree. WordPress core downloads can be
replaced without deleting these plugin files or their database activation state.
The development database configuration template lives outside downloaded core, and
the cloned table prefix is saved in the environment's state.

Later starts preserve data and do not clone again. `make up WP=6.8 PHP=8.3`
selects versions that persist for that environment; use `WP=latest` to return to
current WordPress. `npm run wp-env -- start --update` refreshes wp-env sources.

## Clone from another environment

Choose an existing environment when you want to reproduce an issue using its
courses, users, and plugin settings instead of the default development data:

```sh
make env-list
make up CLONE_FROM=0123456789abcdef
# Equivalent CLI forms:
npm run wp-env -- start --clone-from 0123456789abcdef
npm run wp-env -- start --clone-from=0123456789abcdef
```

Use the environment ID printed by `env-list`, the full Docker project
name shown by `env-list`, or `sensei-lms`. The selected source must be initialized
and running. A missing, stopped, unknown, or unreadable explicit source fails;
the tool never starts or modifies it. An environment cannot clone itself. Source selection is
saved for initialization retries, which resolve the current source container ID.

The selected source supplies both the database and uploads. Plugin files are not
copied: install any additional plugins separately in the target. Their database
settings come from the selected source. Only Sensei is activated during
initialization; activate the installed integration plugins afterward.

For example, if an issue occurs with a particular set of courses and lessons,
keep the environment that reproduces it running. Create a worktree for the fix
and use that environment's ID as `CLONE_FROM`. The copy has independent data and
uploads, so you can test changes without altering the reproduction environment.
Keep the source environment's worktree until you no longer need it.

Cloning only initializes new environments. Passing `--clone-from` to an already
initialized environment prints a skip message and preserves its data. To choose
a different source, explicitly destroy and recreate the target environment.

To initialize a new environment without cloning, use either:

```sh
NO_CLONE=1 make up
npm run wp-env -- start --no-clone
```

These flags do not erase an initialized environment or bypass primary adoption.

An explicit `--clone-from` takes precedence over `--no-clone` or `NO_CLONE=1`.
CI disables cloning.

## Optional private settings

State lives in `~/.local/share/sensei-dev`, outside worktrees. Override this with an
absolute `SENSEI_ENV_HOME` if needed; keep it consistent across commands, since
linked-worktree Docker identities depend on the registry location. The canonical
primary project remains `sensei-lms` and cannot be shared across registries. The directory contains
private settings, ownership records, generated configurations, runtime files,
and uploads.

An optional `settings.json` can set `adminPassword` for first initialization or an
absolute `theme` directory named `course`. Omit these settings for normal use.
Course is downloaded from WordPress.org by default; an adopted primary keeps its
existing local Course directory unless a private theme setting overrides it.
Changing `adminPassword` does not reset accounts on later starts or on adoption.

For new environments, initialization replaces source URLs using
serialization-aware search/replace,
activates Sensei and Course, and creates or updates the local administrator.
Other users and content remain in the copy. Mail is intercepted on development
sites, and automatic WP-Cron and Action Scheduler async dispatch are disabled.
Run specific scheduled work explicitly when testing it. The guard does not apply
to automated test instances.

After an interrupted initialization, fix the reported problem and run
`make env-retry`. This repeats initialization, copying the source again if one was selected and
replacing incomplete development data. Otherwise use `make destroy` and `make up`. Development data is never
imported into the test database. Only `clean tests` and `reset tests` are accepted
through the wrapper; recreate the environment for a fresh development copy.

## Cleanup

```sh
make env-list
make down
make destroy
make env-prune
make env-prune APPLY=1 IDS=0123456789abcdef,fedcba9876543210
```

`down` stops the current worktree's environment while retaining data. `destroy`
removes its containers, volumes, runtime files, and uploads. Unrelated private files,
shared Docker images, and other projects remain intact. External WordPress and
theme directories retained during adoption are not removed. In a linked worktree,
cleanup leaves `sensei-lms` intact. In the primary checkout, `make destroy` explicitly
deletes `sensei-lms` and its databases and uploads; use `make down` to retain them.
Orphan pruning never selects the primary checkout.

Prefer destroying environments before removing or archiving their worktree. If the
checkout disappears first, run `env-prune` from another checkout. It previews
orphans and requires explicit IDs to remove them. Missing directories still
registered with Git are preserved; resolve that registration with Git first. If
the repository itself is unavailable, cleanup conservatively preserves its data.

There is no deletion based on age. Worktree `node_modules` and `vendor` directories
belong to the checkout; removing it reclaims them. Images remain cached.
Lifecycle locks contain process IDs. After an abrupt termination, verify that the
process is gone before removing a stale lock. Failed cleanup retains ownership
records so it can be retried.

## Tooling tests

`npm run test:env` runs focused Node tests without Docker or private fixtures.
Verify Docker isolation using disposable containers, including source reads,
independent target changes, restart persistence, and cleanup. Never reset or
modify the source environment as part of verification.
