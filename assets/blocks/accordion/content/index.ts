/**
 * Internal dependencies
 */
import metadata from './block.json';
/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import edit from './content-edit';
import save from './content-save';

export default {
	...metadata,
	edit,
	save,
};
