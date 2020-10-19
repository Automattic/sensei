import { __ } from '@wordpress/i18n';
import { LessonIcon as icon } from '../../../icons';
import edit from './edit';
import metadata from './block.json';

export default {
	title: __( 'Lessons', 'sensei-lms' ),
	description: __( 'Lessons area.', 'sensei-lms' ),
	icon,
	...metadata,
	edit,
};
