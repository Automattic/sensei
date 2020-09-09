import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

import EditCourseOutlineBlock from './edit';

registerBlockType( 'sensei-lms/course-outline', {
	title: __( 'Course Outline', 'sensei-lms' ),
	description: __( 'Manage your Sensei LMS course outline.', 'sensei-lms' ),
	icon: 'list-view',
	category: 'sensei-lms',
	keywords: [ __( 'Outline', 'sensei-lms' ), __( 'Course', 'sensei-lms' ) ],
	supports: {
		html: false,
		multiple: false,
	},
	edit( props ) {
		return <EditCourseOutlineBlock { ...props } />;
	},
} );
