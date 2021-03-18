/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

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
			selectFirstBlock: true,
			isEmptyBlock: isQuestionEmpty,
		},
		props
	);

	useUpdateQuizHasQuestionsMeta( clientId );

	const [
		isExistingQuestionsModalOpen,
		setExistingQuestionsModalOpen,
	] = useState( false );

	const { isPostTemplate } = props.attributes;

	const openExistingQuestionsModal = () =>
		setExistingQuestionsModalOpen( true );

	const closeExistingQuestionsModal = () =>
		setExistingQuestionsModalOpen( false );

	return (
		<>
			<QuizValidationResult { ...props } />
			<div className="sensei-lms-quiz-block__separator">
				<span>{ __( 'Lesson Quiz', 'sensei-lms' ) }</span>
			</div>
			<InnerBlocks
				allowedBlocks={ [
					'sensei-lms/quiz-question',
					'sensei-lms/quiz-category-question',
				] }
				template={
					isPostTemplate ? [ [ 'sensei-lms/quiz-question', {} ] ] : []
				}
				templateInsertUpdatesSelection={ false }
				renderAppender={ () => (
					<QuizAppender
						clientId={ clientId }
						openModal={ openExistingQuestionsModal }
					/>
				) }
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
