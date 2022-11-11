/**
 * WordPress dependencies
 */
import { link as icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import edit from './course-overview-edit';
import metadata from './block.json';

export default {
	...metadata,
	metadata,
	icon,
	edit,
};
