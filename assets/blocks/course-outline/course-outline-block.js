import { registerBlockType } from '@wordpress/blocks';
import { dispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { extractStructure } from './data';

import EditCourseOutlineBlock from './edit';
import { COURSE_STORE } from './store';

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
	attributes: {
		id: {
			type: 'int',
		},
		blocks: {
			type: 'object',
		},
	},
	edit( props ) {
		return <EditCourseOutlineBlock { ...props } />;
	},
	save( { innerBlocks } ) {
		dispatch( COURSE_STORE ).setEditorStructure(
			extractStructure( innerBlocks )
		);
		return null;
	},
} );
