import { InnerBlocks } from '@wordpress/block-editor';
import { EditQuestionBlock } from './edit';

export default {
	name: 'sensei-lms/quiz-question',
	parent: [ 'sensei-lms/quiz' ],
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
			default: 'multichoice',
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
