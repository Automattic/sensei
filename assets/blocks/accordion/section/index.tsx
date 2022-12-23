/* eslint-disable jsx-a11y/click-events-have-key-events */
/* eslint-disable jsx-a11y/no-noninteractive-element-interactions */
/**
 * Internal dependencies
 */
import metadata from './block.json';
/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import edit from './section-edit';
import save from './section-save';

export default {
	...metadata,
	edit,
	save,
};
