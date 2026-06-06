/**
 * WordPress dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { TemplateSelector } from './template-selector';

const element = document.getElementById( 'sensei-lm-block-template__options' );

if ( element ) {
	createRoot( element ).render( <TemplateSelector /> );
}
