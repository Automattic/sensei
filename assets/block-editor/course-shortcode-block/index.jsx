import { registerBlockType } from '@wordpress/blocks';
import { ServerSideRender } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

registerBlockType( 'sensei-lms/course-shortcode-block', {
	title: __( 'Courses', 'sensei-lms' ),
	icon: 'list',
	category: 'widgets',

	edit: function( props ) {
		return (
			<ServerSideRender
				block="sensei-lms/course-shortcode-block"
				className="sensei-lms-course-shortcode-block"
				attributes={ props.attributes }
			/>
		);
	},

	save: function() {
		return null;
	}
} );
