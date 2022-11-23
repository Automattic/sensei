/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Exit survey reasons
 */
export const reasons = [
	{
		id: 'no-longer-need',
		label: __( 'I no longer need the plugin', 'sensei-lms' ),
	},
	{
		id: 'not-working',
		label: __( "The plugin isn't working", 'sensei-lms' ),
		detailsLabel: __( "What isn't working properly?", 'sensei-lms' ),
	},
	{
		id: 'different-functionality',
		label: __( "I'm looking for different functionality", 'sensei-lms' ),
		detailsLabel: __( 'What functionality is missing?', 'sensei-lms' ),
	},
	{
		id: 'found-better-plugin',
		label: __( 'I found a better plugin', 'sensei-lms' ),
		detailsLabel: __( "What's the name of the plugin?", 'sensei-lms' ),
	},
	{
		id: 'temporary',
		label: __( "It's a temporary deactivation", 'sensei-lms' ),
	},
	{
		id: 'other',
		label: 'Other',
		detailsLabel: __( 'Why are you deactivating?', 'sensei-lms' ),
	},
];
