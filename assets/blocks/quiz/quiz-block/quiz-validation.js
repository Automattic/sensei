/**
 * WordPress dependencies
 */
import { Notice } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import {
	PluginPostStatusInfo,
	PluginPrePublishPanel,
} from '@wordpress/edit-post';
import { useEffect } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	BLOCK_META_STORE,
	setBlockMeta,
} from '../../../shared/blocks/block-metadata';

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

const QuizValidationResult = ( { clientId } ) => {
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

	const isSavingPost = useSelect(
		( select ) =>
			select( 'core/editor' ).isSavingPost() &&
			! select( 'core/editor' ).isAutosavingPost()
	);

	useEffect( () => {
		if ( isSavingPost ) showValidationErrors( incompleteQuestions, false );
	}, [ isSavingPost, incompleteQuestions ] );

	const { selectBlock } = useDispatch( 'core/block-editor' );
	const selectFirstIncompleteQuestionBlock = () => {
		if ( ! incompleteQuestions.length ) return;
		showValidationErrors( incompleteQuestions );
		selectBlock( incompleteQuestions[ 0 ].clientId );
	};

	return (
		<>
			<PluginPostStatusInfo>
				{ incompleteQuestions.length && (
					<IncompleteQuestionsNotice
						onClick={ selectFirstIncompleteQuestionBlock }
						count={ incompleteQuestions.length }
					/>
				) }
			</PluginPostStatusInfo>
			{ incompleteQuestions.length && (
				<PluginPrePublishPanel
					title={ __( 'Lesson Quiz', 'sensei-lms' ) }
					initialOpen={ true }
				>
					<TriggerErrorDisplay errors={ incompleteQuestions } />
					<IncompleteQuestionsNotice
						onClick={ selectFirstIncompleteQuestionBlock }
						count={ incompleteQuestions.length }
					/>
					<p>
						{ __(
							"Incomplete questions won't be displayed to the learner when taking the quiz.",
							'sensei-lms'
						) }
					</p>
				</PluginPrePublishPanel>
			) }
		</>
	);
};

const TriggerErrorDisplay = ( { errors } ) => {
	useEffect( () => {
		showValidationErrors( errors );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	return null;
};

const showValidationErrors = ( blocks, on = true ) => {
	blocks.forEach( ( { clientId: id } ) => {
		setBlockMeta( id, { showValidationErrors: on } );
	} );
};

export default QuizValidationResult;
