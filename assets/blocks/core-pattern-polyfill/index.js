/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';

export default {
	...metadata,
	metadata,
	title: __( 'Pattern', 'sensei-lms' ),
	description: __( 'Show a block pattern.', 'sensei-lms' ),
	edit,
};
