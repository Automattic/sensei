/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Welcome } from './welcome';
import { Purpose } from './purpose';
import Features from './features';
import { Ready } from './ready';

export const steps = [
	{
		key: 'welcome',
		container: <Welcome />,
		label: __( 'Welcome', 'sensei-lms' ),
	},
	{
		key: 'purpose',
		container: <Purpose />,
		label: __( 'Purpose', 'sensei-lms' ),
	},
	{
		key: 'features',
		container: <Features />,
		label: __( 'Features', 'sensei-lms' ),
	},
	{
		key: 'ready',
		container: <Ready />,
		label: __( 'Ready', 'sensei-lms' ),
	},
];
