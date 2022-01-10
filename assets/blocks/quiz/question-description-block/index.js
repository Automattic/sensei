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
import icon from '../../../icons/question.svg';

/**
 * Question description block.
 */
export default {
	...metadata,
	title: __( 'Description', 'sensei-lms' ),
	icon,
	usesContext: [ 'sensei-lms/quizId' ],
	description: __( 'Question Description.', 'sensei-lms' ),
	edit,
	save: () => <InnerBlocks.Content />,
};
