/**
 * Internal dependencies
 */
import icon from '../../icons/lesson-properties.svg';
import metadata from './block.json';
import edit from './lesson-properties-edit';

export default {
	...metadata,
	metadata,
	icon,
	example: {
		attributes: {
			difficulty: 'easy',
			length: 10,
		},
	},
	edit,
	save: () => {
		return null;
	},
};
