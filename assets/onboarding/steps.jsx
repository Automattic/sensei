import { __ } from '@wordpress/i18n';

export const steps = [
	{
		key: 'welcome',
		container: <div>Welcome</div>,
		label: __( 'Welcome', 'sensei-lms' ),
		isComplete: false,
	},
	{
		key: 'purpose',
		container: <div>Purpose</div>,
		label: __( 'Purpose', 'sensei-lms' ),
		isComplete: false,
	},
	{
		key: 'features',
		container: <div>Features</div>,
		label: __( 'Features', 'sensei-lms' ),
		isComplete: false,
	},
	{
		key: 'ready',
		container: <div>Ready</div>,
		label: __( 'Ready', 'sensei-lms' ),
		isComplete: false,
	},
];
