/**
 * WordPress dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Main from './main';

const root = createRoot( document.getElementById( 'sensei-home-page' ) );
root.render( <Main /> );
