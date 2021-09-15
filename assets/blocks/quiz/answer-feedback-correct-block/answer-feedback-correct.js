/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import { compose } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';
/**
 * External dependencies
 */
import cn from 'classnames';

/**
 * Internal dependencies
 */
import { withBlockMeta } from '../../../shared/blocks/block-metadata';



/**
 * Correct Answer Feedback control.
 */
const AnswerFeedbackCorrect = ( ) => {
	return (
		<div className={ cn( 'sensei-lms-question-answer-feedback-correct-block' ) }>
			<h4>{ __( 'Correct Answer Feedback', 'sensei-lms' ) }</h4>
			<InnerBlocks
				template={ [
					[
						'core/paragraph',
						{
							placeholder: __(
								'Correct Answer Feedback',
								'sensei-lms'
							),
						},
					],
				] }
				templateInsertUpdatesSelection={ false }
				templateLock={ false }
			/>
		</div>
	);
};

export default compose(
	withBlockMeta,
)( AnswerFeedbackCorrect );