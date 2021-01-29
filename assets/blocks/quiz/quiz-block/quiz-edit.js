/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Quiz block editor.
 */
const QuizEdit = () => {
	return (
		<>
			<InnerBlocks
				allowedBlocks={ [ 'sensei-lms/quiz-question' ] }
				template={ [ [ 'sensei-lms/quiz-question' ] ] }
			/>
		</>
	);
};

export default QuizEdit;
