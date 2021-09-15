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
 * Question Answer Feedback control.
 */
const AnswerFailedFeedback = ( ) => {
	return (
		<div className={ cn( 'sensei-lms-question-answer-feedback-failed-block' ) }>
			<h4>{ __( 'Failed Answer Feedback', 'sensei-lms' ) }</h4>
			<InnerBlocks
				template={ [
					[
						'core/paragraph',
						{
							placeholder: __(
								'Failed Answer Feedback',
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
)( AnswerFailedFeedback );