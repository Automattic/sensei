import { __ } from '@wordpress/i18n';
import { LessonIcon as icon } from '../../../icons';
import edit from './edit';
import metadata from './block.json';

export default {
	title: __( 'Lesson', 'sensei-lms' ),
	description: __( 'Where your course content lives.', 'sensei-lms' ),
	icon,
	keywords: [ __( 'Course', 'sensei-lms' ), __( 'Lesson', 'sensei-lms' ) ],
	...metadata,
	example: {
		attributes: {
			title: __( 'Start learning', 'sensei-lms' ),
		},
	},
	edit,
};
