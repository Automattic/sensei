/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import UploadPage from './upload';
import ImportProgressPage from './import-progress';
import DonePage from './done';

export const steps = [
	{
		key: 'upload',
		container: <UploadPage />,
		label: __( 'Upload CSV Files', 'sensei-lms' ),
	},
	{
		key: 'progress',
		container: <ImportProgressPage />,
		label: __( 'Import', 'sensei-lms' ),
	},
	{
		key: 'complete',
		container: <DonePage />,
		label: __( 'Done', 'sensei-lms' ),
	},
];
