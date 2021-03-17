/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */
import { withBlockMetaProvider } from '../../../shared/blocks/block-metadata';
import { useAutoInserter } from '../../../shared/blocks/use-auto-inserter';
import questionBlock from '../question-block';
import { useQuizStructure } from '../quiz-store';
import QuizValidationResult from './quiz-validation';
import QuizAppender from './quiz-appender';
import QuizSettings from './quiz-settings';
import { useHasQuestions } from './use-has-questions';

/**
 * Quiz block editor.
 *
 * @param {Object} props
 */
const QuizEdit = ( props ) => {
	useQuizStructure( props );

	useAutoInserter(
		{ name: questionBlock.name, selectFirstBlock: true },
		props
	);

	useHasQuestions( props.clientId );

	const { isPostTemplate } = props.attributes;

	return (
		<>
			<QuizValidationResult { ...props } />
			<div className="sensei-lms-quiz-block__separator">
				<span>{ __( 'Lesson Quiz', 'sensei-lms' ) }</span>
			</div>
			<InnerBlocks
				allowedBlocks={ [ 'sensei-lms/quiz-question' ] }
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

export default withBlockMetaProvider( QuizEdit );
