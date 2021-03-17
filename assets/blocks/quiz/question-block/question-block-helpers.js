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
import { getQuestionBlockValidationErrorMessages } from './question-validation';

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

/**
 * Display validation notice for the question block if there are errors.
 *
 * @param {Object}  props
 * @param {Object}  props.meta
 * @param {Object}  props.attributes
 * @param {string}  props.attributes.type           Question type.
 * @param {Array}   props.meta.validationErrors     Validation errors  codes
 * @param {boolean} props.meta.showValidationErrors Display validation errors.
 */
export const QuestionValidationNotice = ( {
	attributes: { type },
	meta: { validationErrors, showValidationErrors },
} ) => {
	if ( ! showValidationErrors ) return null;

	const validationMessages = getQuestionBlockValidationErrorMessages(
		validationErrors,
		type
	);

	return <BlockValidationNotice errors={ validationMessages } />;
};

/**
 * Display an icon and tooltip if the block has validation errors.
 *
 * @param {string[]} errors Error messages.
 */
export const BlockValidationNotice = ( { errors = [] } ) => {
	if ( ! errors || ! errors.length ) return null;
	const errorItems = errors?.map?.( ( error ) => (
		<li key={ error }>{ error }</li>
	) );
	return (
		<div className="sensei-lms-block-validation__notice">
			<Tooltip
				text={
					<ul className="sensei-lms-block-validation__notice__tooltip-content">
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
