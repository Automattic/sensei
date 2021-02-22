/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useAutoInserter } from '../../../shared/blocks/use-auto-inserter';
import questionBlock from '../question-block';
import { useQuizStructure } from '../quiz-store';
import QuizSettings from './quiz-settings';
import QuestionBankModal from './question-bank-modal';

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

	const [ isQuestionBankOpen, setQuestionBankOpen ] = useState( false );

	const { isPostTemplate } = props.attributes;

	return (
		<>
			<div className="sensei-lms-quiz-block__separator">
				<span>{ __( 'Lesson Quiz', 'sensei-lms' ) }</span>
			</div>
			<InnerBlocks
				allowedBlocks={ [ 'sensei-lms/quiz-question' ] }
				template={
					isPostTemplate ? [ [ 'sensei-lms/quiz-question', {} ] ] : []
				}
				templateInsertUpdatesSelection={ false }
				renderAppender={ () => (
					<QuestionBankModal.Opener setOpen={ setQuestionBankOpen } />
				) }
			/>
			<div className="sensei-lms-quiz-block__separator" />

			{ isQuestionBankOpen && (
				<QuestionBankModal setOpen={ setQuestionBankOpen } />
			) }

			<QuizSettings { ...props } />
		</>
	);
};

export default QuizEdit;
