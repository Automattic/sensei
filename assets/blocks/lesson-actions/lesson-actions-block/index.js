/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './lesson-actions-edit';
import save from './lesson-actions-save';
import icon from '../../../icons/buttons.svg';

export default {
	...metadata,
	metadata,
	example: {
		innerBlocks: [
			{ name: 'sensei-lms/button-complete-lesson' },
			{ name: 'sensei-lms/button-next-lesson' },
			{ name: 'sensei-lms/button-reset-lesson' },
		],
	},
	icon,
	edit,
	save,
};
