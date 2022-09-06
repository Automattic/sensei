/**
 * WordPress dependencies
 */
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Templates } from './templates';

const element = document.getElementById( 'sensei-lm-block-template__options' );

render( <Templates />, element );
