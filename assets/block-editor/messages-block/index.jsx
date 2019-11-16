/**
 * WordPress dependencies.
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import Block from './block';

registerBlockType( 'sensei-lms/messages-block', {
	title: __( 'Sensei LMS Messages', 'sensei-lms' ),
	icon: 'email',
	category: 'widgets',

	edit: function( props ) {
		return <Block { ...props } />
	},

	save: function() {
		return (
			<div class="wp-block-sensei-messages"></div>
		);
	}
} );
