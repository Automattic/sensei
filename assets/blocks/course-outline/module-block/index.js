import { InnerBlocks } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { ModuleIcon } from '../../../icons';

import edit from './edit';

registerBlockType( 'sensei-lms/course-outline-module', {
	title: __( 'Module', 'sensei-lms' ),
	description: __( 'Used to group one or more lessons.', 'sensei-lms' ),
	icon: ModuleIcon,
	category: 'sensei-lms',
	parent: [ 'sensei-lms/course-outline' ],
	keywords: [ __( 'Outline', 'sensei-lms' ), __( 'Module', 'sensei-lms' ) ],
	supports: {
		html: false,
	},
	attributes: {
		id: {
			type: 'integer',
		},
		title: {
			type: 'string',
			default: '',
		},
		description: {
			type: 'string',
			default: '',
		},
		mainColor: {
			type: 'string',
		},
		customMainColor: {
			type: 'string',
		},
		textColor: {
			type: 'string',
		},
		customTextColor: {
			type: 'string',
		},
		className: {
			type: 'string',
		},
		customClassName: {
			type: 'string',
		},
	},
	example: {
		attributes: {
			title: 'Module',
			description: 'About Module',
			innerBlocks: [
				{
					name: 'sensei-lms/course-outline-lesson',
					attributes: {
						title: 'Lesson',
					},
				},
			],
		},
	},
	styles: [
		{
			name: 'minimal',
			label: 'Minimal',
		},
		{
			name: 'none',
			label: 'None',
		},
		{
			name: 'default',
			label: 'Default',
			isDefault: true,
		},
	],
	edit,
	save() {
		return <InnerBlocks.Content />;
	},
} );
