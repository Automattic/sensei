/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useAutoInserter } from '../../../shared/blocks/use-auto-inserter';
import questionBlock from '../question-block';
import { useQuizStructure } from '../quiz-store';
import QuizAppender from './quiz-appender';
import QuizSettings from './quiz-settings';
import { useUpdateQuizHasQuestionsMeta } from './use-update-quiz-has-questions-meta';
import { isQuestionEmpty } from '../data';

/**
 * Quiz block editor.
 *
 * @param {Object} props
 */
const QuizEdit = ( props ) => {
	useQuizStructure( props );

	useAutoInserter(
		{
			name: questionBlock.name,
			selectFirstBlock: true,
			isEmptyBlock: isQuestionEmpty,
		},
		props
	);

	useUpdateQuizHasQuestionsMeta( props.clientId );

	const { isPostTemplate } = props.attributes;

	return (
		<>
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
				renderAppender={ () => <QuizAppender { ...props } /> }
			/>
			<div className="sensei-lms-quiz-block__separator" />
			<QuizSettings { ...props } />
		</>
	);
};

export default QuizEdit;
