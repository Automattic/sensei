import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

import Edit from './edit';

registerBlockType( 'sensei-lms/course-outline-module', {
	title: __( 'Module', 'sensei-lms' ),
	description: __( 'Used to group one or more lessons.', 'sensei-lms' ),
	icon: 'list-view',
	category: 'sensei-lms',
	parent: [ 'sensei-lms/course-outline' ],
	keywords: [ __( 'Outline', 'sensei-lms' ), __( 'Module', 'sensei-lms' ) ],
	supports: {
		html: false,
		customClassName: false,
	},
	attributes: {
		id: {
			type: 'int',
		},
		title: {
			type: 'string',
		},
		description: {
			type: 'string',
		},
		lessons: {
			type: 'array',
		},
	},
	edit( props ) {
		return <Edit { ...props } />;
	},
} );
