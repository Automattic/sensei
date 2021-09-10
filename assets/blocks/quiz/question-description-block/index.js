/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import edit from './question-description';
import metadata from './block.json';
import icon from '../../../icons/question-description-icon';

/**
 * Quiz category question description definition.
 */
export default {
	...metadata,
	title: __( 'Question Description Block', 'sensei-lms' ),
	icon,
	usesContext: [ 'sensei-lms/quizId' ],
	description: __( 'Question Desription.', 'sensei-lms' ),
	/*example: {
		attributes: {
			categoryName: __( 'Example Category', 'sensei-lms' ),
		},
	},*/
	edit,
	save: () => <InnerBlocks.Content />,
};
