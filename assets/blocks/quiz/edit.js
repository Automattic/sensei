import { InnerBlocks } from '@wordpress/block-editor';

export const EditQuizBlock = () => {
	return (
		<>
			<InnerBlocks
				allowedBlocks={ [ 'sensei-lms/quiz-question' ] }
				template={ [ [ 'sensei-lms/quiz-question' ] ] }
			/>
		</>
	);
};
