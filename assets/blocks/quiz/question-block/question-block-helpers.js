/**
 * WordPress dependencies
 */
import { Tooltip } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Icon, info } from '@wordpress/icons';
/**
 * Internal dependencies
 */
import { alert } from '../../../icons/wordpress-icons';
import { getValidationErrorMessage } from './question-validation';

/**
 * Display a notice about the question being shared across quizzes.
 */
export const SharedQuestionNotice = () => (
	<div className="sensei-lms-question-block__notice">
		<Icon icon={ info } />
		<Tooltip
			text={ __(
				'Any updates made to this question will also update it in any other quiz that includes it.',
				'sensei-lms'
			) }
		>
			<span>{ __( 'Shared Question', 'sensei-lms' ) }</span>
		</Tooltip>
	</div>
);

export const QuestionValidationErrors = ( { errors = [], type } ) => {
	if ( ! errors || ! errors.length ) return null;
	const errorItems = errors?.map?.( ( error ) => (
		<li key={ error }>{ getValidationErrorMessage( error, type ) }</li>
	) );
	return (
		<div className="sensei-lms-question-block__error">
			<Tooltip
				text={
					<ul className="sensei-lms-question-block__error__tooltip-content">
						{ errorItems }
					</ul>
				}
			>
				<span>
					<Icon icon={ alert } size={ 32 } />
				</span>
			</Tooltip>
		</div>
	);
};
