/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

export const DIFFICULTIES = [
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
];
