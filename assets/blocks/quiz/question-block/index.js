/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import edit from './question-edit';
import metadata from './block.json';
import icon from '../../../icons/question-icon';

/**
 * Quiz question block definition.
 */
export default {
	...metadata,
	title: __( 'Question', 'sensei-lms' ),
	icon,
	description: __(
		'Question for the student with various answer options.',
		'sensei-lms'
	),
	example: {
		attributes: { title: __( 'Example Quiz Question', 'sensei-lms' ) },
	},
	edit,
	save: () => <InnerBlocks.Content />,
};
