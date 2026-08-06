# Naming Conventions

## General

### Tracks events

Follow the data team's [Tracks Event Design & Naming Conventions](https://fieldguide.automattic.com/data-at-a8c/data-tools/tracks/tracks-for-developers-product-teams/tracks-naming-conventions/).

### Student, not learner

Use "student" in new identifiers, comments, and user-facing strings. "Learner" only survives in existing public APIs and database keys that cannot be renamed.

## PHP

### Hooks

- Lowercase letters, words separated by underscores — not spaces or dashes.
- Prefix with `sensei_`, then the context, then the thing:
  - Filter: `sensei_course_pricing_description`, `sensei_course_archive_page_url`.
  - Action: `sensei_extensions_header`, `sensei_course_results_before_lessons`.
- Do not abbreviate unnecessarily. Hook names should be unambiguous and self-documenting.
- **Never build hook names programmatically.** Hardcode them so they stay greppable.

  ```php
  // Bad — not searchable.
  do_action( "sensei_{$post_type}_saved", $post_id );

  // Good.
  if ( 'course' === $post_type ) {
      do_action( 'sensei_course_saved', $post_id );
  }
  ```

### File naming

Per the [WordPress PHP coding standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/#naming-conventions): class file names are the class name with `class-` prepended and underscores replaced by hyphens.

`WP_Error` → `class-wp-error.php`.

### Class naming

Global namespace (no `namespace` declared) → prefix the class with `Sensei_`, since there is no namespace keeping it from colliding with other plugins, themes, or core:

```php
// includes/class-sensei-course-theme-course-content.php
class Sensei_Course_Theme_Course_Content { ... }
```

Namespaced → no prefix:

```php
// includes/course-theme/class-course-content.php
namespace Sensei\Course_Theme;

class Course_Content { ... }
```

### Abstract classes and interfaces

Suffix with `_Abstract` and `_Interface` respectively: `Migration_Abstract`, `Course_Progress_Repository_Interface`.

### Meta keys

- Underscores between words.
- Prefix with `sensei_`.
- Private meta (not user-editable) starts with an underscore: `_sensei_email_description`.

## JavaScript

### Hooks

- Dotted segments: the `sensei` root, then the context, then the thing.
- Segments are camelCase:
  - Filter: `sensei.setupWizard.welcomeTitle`.
  - Action: `sensei.videoProgression.videoEnded`.
- Do not abbreviate unnecessarily; do not build hook names programmatically.

### Block directory structure

```
assets/blocks/
  [BLOCK_NAME]-block/            # the -block suffix marks block folders
  [SUBCONTEXT]/                  # group of related blocks, e.g. course-outline
    [BLOCK_NAME]-block/
```

### Block file naming

| File | Purpose |
| --- | --- |
| `block.json` | Block metadata used in frontend and backend. |
| `index.js` | Exports the block component object. |
| `[BLOCK_NAME]-edit.js` | Block `edit` function. |
| `[BLOCK_NAME]-save.js` | Block `save` function. |
| `[BLOCK_NAME]-settings.js` | Settings component, instanced in the block's edit render. |
| `[BLOCK_NAME]-frontend.js` | Script loaded only on the frontend, if needed. |
| `[BLOCK_NAME].scss` | Styles for editor and frontend. |
| `[BLOCK_NAME]-editor.scss` | Editor-only styles. |
| `[BLOCK_NAME]-frontend.scss` | Frontend-only styles, if needed. |

### Named vs. default export

When the export is the main purpose of the file, make it the default export, and match the exported function's name to the filename (or to the folder name for `index.js`).

### Import ordering

```js
/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { RichText } from '@wordpress/block-editor';
import { getBlockDefaultClassName } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { getButtonProps, getButtonWrapperProps } from './button-props';
```

## CSS

[BEM](https://getbem.com/naming/), with these caveats:

- Prefix every class with `sensei` to avoid collisions with other plugins, themes, and core.
- Block classes are prefixed `.wp-block-sensei-[block-name]` (e.g. `wp-block-sensei-course-outline`).
- Sort properties alphabetically.

## Tests

See [unit-tests.md](unit-tests.md).
