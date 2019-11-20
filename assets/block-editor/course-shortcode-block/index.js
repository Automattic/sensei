/**
 * Internal dependencies
 */
import { __ } from '@wordpress/i18n';
import edit from './edit';
import save from './save';
import { registerBlockType } from '@wordpress/blocks';

const name = 'course-shortcode-block';
const title = __( 'Course List', 'sensei-lms' );

/* From https://material.io/tools/icons */
const icon = (
	<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
		<path d="M4 14h4v-4H4v4zm0 5h4v-4H4v4zM4 9h4V5H4v4zm5 5h12v-4H9v4zm0 5h12v-4H9v4zM9 5v4h12V5H9z" />
		<path d="M0 0h24v24H0z" fill="none" />
	</svg>
);

const settings = {
	title,
	icon,
	category: 'sensei-lms',
	keywords: [ __( 'courses', 'sensei-lms' ), 'sensei' ],
	description: __( 'Insert a course list.', 'sensei-lms' ),
	attributes: {
		/**
		 * Post ID(s) to exclude from courses list (separated by commas).
		 */
		exclude: {
			type: 'string',
			default: '',
		},
		/**
		 * Post ID(s) to show in list (separated by commas).
		 */
		ids: {
			type: 'string',
			default: '',
		},
		/**
		 * Number of courses to show.
		 */
		number: {
			type: 'number',
			default: 10,
		},
		/**
		 * Order direction field.
		 */
		order: {
			type: 'string',
			default: 'DESC',
		},
		/**
		 * Order field.
		 */
		orderby: {
			type: 'string',
			default: 'date',
		},
		/**
		 * Teacher ID(s) to show courses for.
		 */
		teacher: {
			type: 'string',
			default: '',
		},
	},
	supports: {
		html: false,
		align: true,
	},
	edit,
	save,
};

registerBlockType( `sensei-lms/${ name }`, settings );
