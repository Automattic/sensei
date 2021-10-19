/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { Icon } from '@wordpress/icons';

/**
 * External dependencies
 */
import cn from 'classnames';
/**
 * Internal dependencies
 */
import icon from '../../../icons/answer-feedback-correct';

/**
 * Correct Answer Feedback control.
 */
const AnswerFeedbackCorrect = () => {
	return (
		<div
			className={ cn(
				'sensei-lms-question-answer-feedback-correct-block'
			) }
		>
			<Icon icon={ icon } className={ 'icon' } />
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

export default AnswerFeedbackCorrect;
