import { GapFillAnswer } from './gap-fill';
import { MultipleChoiceAnswer } from './multiple-choice';
import { OpenEndedAnswer } from './open-ended';

export default {
	multichoice: {
		title: 'Multiple Choice',
		description: 'Select one or more answers from a list of choices.',
		edit: MultipleChoiceAnswer,
	},
	truefalse: {
		title: 'True / False',
		description: 'True or false question.',
		edit: MultipleChoiceAnswer,
	},
	gap: {
		title: 'Gap Fill',
		description: 'Fill in the missing part of a sentence.',
		edit: GapFillAnswer,
	},
	open: {
		title: 'Open-Ended',
		description: 'Require a written answer..',
		edit: OpenEndedAnswer,
	},
	file: {
		title: 'File Upload',
		description: 'Require a file to be uploaded.',
		edit: OpenEndedAnswer,
	},
};
