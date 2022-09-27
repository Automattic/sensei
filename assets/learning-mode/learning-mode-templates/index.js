/**
 * WordPress dependencies
 */
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { TemplateSelector } from './template-selector';

const element = document.getElementById( 'sensei-lm-block-template__options' );

render( <TemplateSelector />, element );
