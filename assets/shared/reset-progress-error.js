/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

export const resetProgressErrorMessage = () =>
	__(
		"We couldn't finish resetting progress. Some progress or quiz answers may already have been cleared. Refresh the page and review student progress before taking further action. If it looks inconsistent, contact your site administrator.",
		'sensei-lms'
	);
