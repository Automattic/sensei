import { InnerBlocks } from '@wordpress/block-editor';
import { EditQuestionBlock } from './edit';

export default {
	name: 'sensei-lms/quiz-question',
	//category: 'sensei-lms',
	title: 'Question',
	supports: {
		html: false,
	},
	attributes: {
		id: {
			type: 'integer',
		},
		title: {
			type: 'string',
		},
		type: {
			type: 'string',
		},
		answer: {
			type: 'object',
		},
		grade: {
			type: 'integer',
		},
	},
	edit: EditQuestionBlock,
	save: () => <InnerBlocks.Content />,
};
