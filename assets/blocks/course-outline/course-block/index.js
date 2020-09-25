import { __ } from '@wordpress/i18n';
import { CourseIcon as icon } from '../../../icons';
import metadata from './block.json';

import edit from './edit';
import save from './save';

export default {
	title: __( 'Course Outline', 'sensei-lms' ),
	description: __( 'Manage your Sensei LMS course outline.', 'sensei-lms' ),
	keywords: [ __( 'Outline', 'sensei-lms' ), __( 'Course', 'sensei-lms' ) ],
	...metadata,
	icon,
	edit,
	save,
};
