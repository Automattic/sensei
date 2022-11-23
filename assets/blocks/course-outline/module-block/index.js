/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import icon from '../../../icons/module.svg';
import edit from './module-edit';
import transforms from './transforms';
import metadata from './block.json';

export default {
	...metadata,
	metadata,
	icon,
	example: {
		attributes: {
			title: __( 'Module', 'sensei-lms' ),
			description: __( 'About Module', 'sensei-lms' ),
		},
		innerBlocks: [
			{
				name: 'sensei-lms/course-outline-lesson',
				attributes: {
					title: __( 'Lesson', 'sensei-lms' ),
					id: 1,
					draft: false,
					isExample: true,
				},
			},
		],
	},
	styles: [
		{
			name: 'default',
			label: __( 'Filled', 'sensei-lms' ),
			isDefault: true,
		},
		{
			name: 'minimal',
			label: __( 'Minimal', 'sensei-lms' ),
		},
	],
	transforms,
	edit,
	save() {
		return <InnerBlocks.Content />;
	},
};
