# Dependency Testing Strategies

Read only the sections that match the package's actual consumer. A dependency
can span multiple sections; combine the smallest meaningful checks rather than
defaulting to every command.

## Runtime PHP or Composer libraries

- Install through `make install-php` so the locked dependency graph and Composer
  scripts match Sensei's wp-env workflow.
- If the package enters the scoped vendor tree, verify the scoped build or plugin
  build actually contains and loads the required classes, functions, and files.
  Class autoload success does not prove loose function files are available.
- Run the smallest unit test that reaches the integration, then exercise the
  actual runtime path when CI cannot observe it.
- For generated HTML or email CSS, inspect the rendered output and logs, not
  merely whether the PHP call returned.

Example: an Emogrifier update affects Sensei email CSS inlining. The meaningful
check is a rendered email or preview with styles visibly applied and no runtime
fatal; a successful Composer install alone is insufficient. See Sensei PR
`#8143` for this risk shape.

## Runtime JavaScript, editor, CSS, and visual libraries

- Trace the importing component and its editor/frontend surface.
- Run its focused Jest test when one exists and rebuild the affected assets.
- Use the `e2e-testing` skill for the smallest affected browser flow. Exercise
  the interaction, inspect the resulting UI at relevant viewport sizes, check
  the console when possible, and capture screenshots for visual changes.
- Verify behavior, not only that the page loads or the bundle compiles.

## Build tools, webpack plugins, loaders, and asset generators

- Identify the exact configuration call site and generated outputs.
- Run `npm ci`, then the narrow build command that invokes the package. Common
  Sensei paths are `npm run build:assets` and `npm run build:combine-assets`.
- Inspect a meaningful artifact: generated dependency metadata, copied files,
  sprite validity and symbol count, bundle entries, or packaged ZIP contents.
- Run `make build` only when the package affects final packaging or Composer's
  scoped vendor tree; do not use it as a universal dependency smoke test.
- Browser QA is unnecessary when the package changes build mechanics but the
  executable implementation and generated output are unchanged and CI covers
  the pipeline.

Example: `@wordpress/dependency-extraction-webpack-plugin` is consumed by
`webpack.config.js`. `build:assets` and `build:combine-assets`, plus inspection
of `assets/dist/assets.json`, exercise the relevant behavior. Broad browser QA
does not add confidence for a package-only release with identical executable
contents. See Sensei PR `#8297`.

## Test, lint, formatting, and developer tools

- Invoke the exact repository script or hook that consumes the tool.
- Use a representative focused file or fixture when success depends on staged
  files, transforms, module loading, or configuration discovery.
- Confirm both the tool's version/graph and the observable result. A package
  being present in `node_modules` is not enough.
- Do not add product browser testing when the dependency cannot enter product
  bundles or runtime code.

## Documentation generators

- Run the documentation command that loads the package, commonly
  `npm run build:docs`.
- Inspect the expected generated entry point and a representative documented
  hook or symbol.
- Trace custom templates and undeclared imports; documentation tools often load
  packages indirectly.

## wp-env, Playwright, and development servers

- Exercise the command that owns the dependency rather than substituting a
  production build.
- For wp-env changes, start the disposable environment and verify the relevant
  WordPress and PHP configuration.
- For Playwright tooling, run the smallest affected spec or setup path.
- For a development server, prove that the server/watch mode starts and serves
  assets; a one-time production compilation may not cover it.

## GitHub Actions dependencies

- Inspect all `uses:` call sites, inputs, permissions, runtime changes, and
  upstream breaking/security notes.
- Validate YAML structure if it changed, but treat the current-head workflow run
  as the primary execution evidence.
- Confirm permission or artifact behavior when the action performs writes,
  uploads, releases, or authentication; a green unrelated workflow is not
  coverage.

## Transitive and security updates

- Use `npm explain` / `npm ls --all` or `composer why` to identify every parent
  path affected by the updated package.
- Map testing to those parents' actual consumers. Do not present the entire
  `npm audit` or Composer advisory inventory as caused by the PR.
- Verify whether the lockfile update actually resolves the scoped advisory and
  whether multiple installed versions leave an affected copy behind.
- Do not run automatic bulk remediation such as `npm audit fix`; it changes the
  review scope.

## Engine, module-system, and peer-dependency changes

- Compare the new engine range with `.nvmrc`, `package.json`, Composer platform
  constraints, and the plugin's supported PHP/WordPress versions as applicable.
- Inspect CommonJS/ESM exports and load the package through Sensei's real caller.
- Treat peer requirements as project obligations even when npm happens to
  resolve a compatible transitive copy.
- Stop on peer conflicts or unsupported runtimes; do not hide them with install
  flags.
