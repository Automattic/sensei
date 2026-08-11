/**
 * WordPress dependencies
 */
import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import CommandPalette from './command-palette';

// Mounted directly (rather than via `registerPlugin`) so the commands
// register regardless of the current screen. `registerPlugin` only renders
// through a `<PluginArea>`, which the block editor provides but plain
// wp-admin screens don't, so commands would never register outside the
// editor. Wrapped in `domReady()` because this script is enqueued with
// `in_footer` — matches the `assets/home/index.js` pattern — and the guard
// keeps it correct even if the enqueue call ever drops the footer flag.
domReady( () => {
	const container = document.createElement( 'div' );
	document.body.appendChild( container );
	createRoot( container ).render( <CommandPalette /> );
} );
