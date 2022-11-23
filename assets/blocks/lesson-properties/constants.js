/**
 * WordPress dependencies
 */
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

export const DIFFICULTIES = applyFilters( 'sensei-lms.Lesson.difficulties', [
	{
		label: __( 'None', 'sensei-lms' ),
		value: '',
	},
	{
		label: __( 'Easy', 'sensei-lms' ),
		value: 'easy',
	},
	{
		label: __( 'Standard', 'sensei-lms' ),
		value: 'std',
	},
	{
		label: __( 'Hard', 'sensei-lms' ),
		value: 'hard',
	},
] );
