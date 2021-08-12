/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { useState, useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { withBlockMetaProvider } from '../../../shared/blocks/block-metadata';
import { useAutoInserter } from '../../../shared/blocks/use-auto-inserter';
import questionBlock from '../question-block';
import { useQuizStructure } from '../quiz-store';
import QuizValidationResult from './quiz-validation';
import QuizAppender from './quiz-appender';
import QuestionsModal from './questions-modal';
import QuizSettings from './quiz-settings';
import { useUpdateQuizHasQuestionsMeta } from './use-update-quiz-has-questions-meta';
import { isQuestionEmpty } from '../data';

const ALLOWED_BLOCKS = [
	'sensei-lms/quiz-question',
	'sensei-lms/quiz-category-question',
];

/**
 * Quiz block editor.
 *
 * @param {Object} props
 */
const QuizEdit = ( props ) => {
	const { clientId } = props;
	useQuizStructure( props );

	useAutoInserter(
		{
			name: questionBlock.name,
			selectFirstBlock: ! props.attributes.isPostTemplate,
			isEmptyBlock: isQuestionEmpty,
		},
		props
	);

	useUpdateQuizHasQuestionsMeta( clientId );

	const [
		isExistingQuestionsModalOpen,
		setExistingQuestionsModalOpen,
	] = useState( false );

	const closeExistingQuestionsModal = () =>
		setExistingQuestionsModalOpen( false );

	/* Temporary solution. See https://github.com/WordPress/gutenberg/pull/29911 */
	const AppenderComponent = useCallback(
		() => (
			<QuizAppender
				clientId={ clientId }
				openModal={ () => setExistingQuestionsModalOpen( true ) }
			/>
		),
		[ clientId ]
	);

	return (
		<>
			<QuizValidationResult { ...props } />
			<div className="sensei-lms-quiz-block__separator">
				<span>{ __( 'Lesson Quiz', 'sensei-lms' ) }</span>
			</div>
			<InnerBlocks
				allowedBlocks={ ALLOWED_BLOCKS }
				templateInsertUpdatesSelection={ false }
				renderAppender={ AppenderComponent }
			/>
			{ isExistingQuestionsModalOpen && (
				<QuestionsModal
					clientId={ clientId }
					onClose={ closeExistingQuestionsModal }
				/>
			) }
			<div className="sensei-lms-quiz-block__separator" />
			<QuizSettings { ...props } />
		</>
	);
};

export default withBlockMetaProvider( QuizEdit );
