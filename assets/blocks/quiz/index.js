import { InnerBlocks } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import { EditQuizBlock } from './edit';
import questionBlock from './question-block';

const quizBlock = {
	name: 'sensei-lms/quiz',
	title: 'Quiz',
	//category: 'sensei-lms',
	supports: {
		html: false,
	},
	attributes: {
		id: {
			type: 'integer',
		},
	},
	edit: EditQuizBlock,
	save: () => <InnerBlocks.Content />,
};

registerBlockType( quizBlock.name, quizBlock );
registerBlockType( questionBlock.name, questionBlock );
