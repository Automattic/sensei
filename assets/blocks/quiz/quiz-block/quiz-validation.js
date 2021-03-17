/**
 * WordPress dependencies
 */
import { Notice } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import {
	PluginPostStatusInfo,
	PluginPrePublishPanel,
} from '@wordpress/edit-post';
import { useCallback } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { BLOCK_META_STORE } from '../../../shared/blocks/block-metadata';
import { Effect, usePostSavingEffect } from '../../../shared/helpers/blocks';

/**
 * Notice about incomplete questions in the quiz.
 *
 * @param {Object}   props
 * @param {number}   props.count   Incomplete question count.
 * @param {Function} props.onClick Callback for notice action.
 */
const IncompleteQuestionsNotice = ( { count, onClick } ) => (
	<div>
		<Notice
			isDismissible={ false }
			status="warning"
			className="sensei-lms-quiz-block__pre-publish-validation__notice"
			actions={ [
				{
					label: __( 'View issues', 'sensei-lms' ),
					onClick,
					className: 'is-link',
				},
			] }
		>
			{ sprintf(
				// Translators: placeholder is the numer of incomplete questions.
				_n(
					'There is %d incomplete question in your lesson quiz.',
					'There are %d incomplete questions in your lesson quiz.',
					count,
					'sensei-lms'
				),
				count
			) }
		</Notice>
	</div>
);

/**
 * Collect and act on validation results for the questions in the quiz.
 *
 * Displays notices in the pre-publish and post status panels if there are incomplete questions.
 *
 * @param {Object}   props
 * @param {string}   props.clientId
 * @param {Function} props.setMeta
 */
const QuizValidationResult = ( { clientId, setMeta } ) => {
	const incompleteQuestions = useSelect(
		( select ) => {
			const questionBlocks = select( 'core/block-editor' ).getBlocks(
				clientId
			);
			const errors = select( BLOCK_META_STORE ).getMultipleBlockMeta(
				questionBlocks.map( ( block ) => block.clientId ),
				'validationErrors'
			);
			return questionBlocks
				.map( ( block ) => ( {
					...block,
					errors: errors[ block.clientId ],
				} ) )
				.filter( ( q ) => q.errors?.length );
		},
		[ clientId ]
	);

	const toggleValidationErrors = useCallback(
		( on = true ) => {
			setMeta( { showValidationErrors: on } );
		},
		[ setMeta ]
	);

	const { selectBlock } = useDispatch( 'core/block-editor' );
	const selectFirstIncompleteQuestionBlock = () => {
		if ( ! incompleteQuestions.length ) return;
		toggleValidationErrors( true );
		selectBlock( incompleteQuestions[ 0 ].clientId );
	};

	usePostSavingEffect( () => toggleValidationErrors( false ), [
		toggleValidationErrors,
	] );

	if ( ! incompleteQuestions.length ) return null;

	const notice = (
		<IncompleteQuestionsNotice
			onClick={ selectFirstIncompleteQuestionBlock }
			count={ incompleteQuestions.length }
		/>
	);

	return (
		<>
			<PluginPostStatusInfo>{ notice }</PluginPostStatusInfo>
			<PluginPrePublishPanel
				title={ __( 'Lesson Quiz', 'sensei-lms' ) }
				initialOpen={ true }
			>
				<Effect onMount={ toggleValidationErrors } />
				{ notice }
				<p>
					{ __(
						"Incomplete questions won't be displayed to the learner when taking the quiz.",
						'sensei-lms'
					) }
				</p>
			</PluginPrePublishPanel>
		</>
	);
};

export default QuizValidationResult;
