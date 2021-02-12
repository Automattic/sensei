/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { useAutoInserter } from '../../../shared/blocks/use-auto-inserter';
import questionBlock from '../question-block';

/**
 * Quiz block editor.
 *
 * @param {Object} props
 */
const QuizEdit = ( props ) => {
	useAutoInserter( { name: questionBlock.name }, props );
	return (
		<>
			<div className="sensei-lms-quiz-block__separator">
				<span>{ __( 'Lesson Quiz', 'sensei-lms' ) }</span>
			</div>
			<InnerBlocks
				allowedBlocks={ [ 'sensei-lms/quiz-question' ] }
				template={ [ [ 'sensei-lms/quiz-question' ] ] }
			/>
			<div className="sensei-lms-quiz-block__separator" />
		</>
	);
};

export default QuizEdit;
