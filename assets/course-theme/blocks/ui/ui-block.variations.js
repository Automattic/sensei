/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import meta from './ui.block.json';

/**
 * Settings shared between variations.
 */
const shared = {
	scope: [ 'inserter' ],
	isActive: [ 'elementClass' ],
};

const row = [
	'core/group',
	{
		style: {
			spacing: {
				padding: { right: '24px', left: '24px' },
				margin: { top: '0px', bottom: '0px' },
			},
		},
		layout: {
			type: 'flex',
			flexWrap: 'nowrap',
			justifyContent: 'space-between',
		},
	},
];

/**
 * Generate block template for a variation.
 *
 * @param {Object} variation  Block variation.
 * @param {Object} attributes Additional attributes.
 * @return {Array} Inner block template.
 */
const createBlockTemplate = ( variation, attributes ) => [
	meta.name,
	{
		...variation.attributes,
		...attributes,
	},
];

/**
 * Fixed header UI block variation definition.
 */
export const FixedHeaderBlock = {
	...shared,
	name: 'sensei-lms/learning-mode-header',
	title: __( 'Fixed Header', 'sensei-lms' ),
	description: __(
		'Header area that stays fixed on top of the screen.',
		'sensei-lms'
	),
	isDefault: true,
	attributes: {
		elementClass: 'sensei-course-theme__header',
	},
	innerBlocks: [ row ],
	meta: {
		isFrame: true,
	},
};

/**
 * Sidebar UI block variation definition.
 */
export const SidebarBlock = {
	...shared,
	name: 'sensei-lms/sidebar',
	title: __( 'Sidebar Menu', 'sensei-lms' ),
	description: __(
		'A sidebar displayed as an overlay menu on mobile screens. Add the "Sidebar Toggle" block to a header to allow opening and closing.',
		'sensei-lms'
	),
	attributes: {
		elementClass: 'sensei-course-theme__sidebar',
	},
	meta: {
		isFrame: true,
	},
};

/**
 * Main Content UI block variation definition.
 */
export const MainContentBlock = {
	...shared,
	name: 'sensei-lms/main-content',
	title: __( 'Main Content', 'sensei-lms' ),
	description: __( 'Content Area.', 'sensei-lms' ),
	attributes: {
		elementClass: 'sensei-course-theme__main-content',
	},
	meta: {
		isFrame: false,
	},
};

/**
 * Two-column layout UI block variation definition.
 */
export const TwoColumnLayoutBlock = {
	...shared,
	name: 'sensei-lms/main-columns',
	title: __( 'Two-column Layout', 'sensei-lms' ),
	description: __(
		'Layout with a fixed left or right sidebar.',
		'sensei-lms'
	),
	attributes: {
		elementClass: 'sensei-course-theme__columns',
	},
	innerBlocks: [
		createBlockTemplate( MainContentBlock, {
			lock: {
				remove: true,
				move: false,
			},
		} ),
		createBlockTemplate( SidebarBlock, {
			lock: {
				remove: true,
				move: false,
			},
		} ),
	],
	meta: {
		innerBlocksProps: {
			orientation: 'horizontal',
			allowedBlocks: [],
			renderAppender: false,
		},
		isFrame: false,
	},
};

/**
 * Video container UI block variation definition.
 */
export const VideoContainerBlock = {
	...shared,
	name: 'sensei-lms/video-container',
	title: __( 'Video Container', 'sensei-lms' ),
	description: __( 'Container for a video with a sidebar.', 'sensei-lms' ),
	attributes: {
		elementClass: 'sensei-course-theme__video-container',
	},
	innerBlocks: [
		[ 'core/video', [] ],
		createBlockTemplate( SidebarBlock, {
			lock: {
				remove: true,
				move: false,
			},
		} ),
	],
	meta: {
		innerBlocksProps: {
			orientation: 'horizontal',
		},
		isFrame: false,
	},
};

/**
 * Navigation area in the footer.
 */
export const ContentFooterBlock = {
	...shared,
	name: 'sensei-lms/content-footer',
	title: __( 'Content Footer', 'sensei-lms' ),
	description: __( 'Navigation area below the content.', 'sensei-lms' ),
	attributes: {
		elementClass: 'sensei-course-theme__content-footer',
	},
	innerBlocks: [ row ],
	meta: {
		isFrame: true,
	},
};

export default [
	FixedHeaderBlock,
	TwoColumnLayoutBlock,
	MainContentBlock,
	SidebarBlock,
	VideoContainerBlock,
	ContentFooterBlock,
];
