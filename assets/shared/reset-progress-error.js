/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

export const resetProgressErrorMessage = () =>
	__(
		'Unable to reset progress. Some changes may have been saved. Review student progress before trying again.',
		'sensei-lms'
	);
