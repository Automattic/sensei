/**
 * WordPress dependencies
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { Icon, layout } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import meta from './ui.block.json';

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

const header = {
	...shared,
	name: 'sensei-lms/learning-mode-header',
	title: __( 'Header', 'sensei-lms' ),
	description: __( 'Fixed header', 'sensei-lms' ),
	isDefault: true,
	attributes: {
		elementClass: 'sensei-course-theme__header',
	},
	innerBlocks: [ row ],
};

const footer = {
	...shared,
	name: 'sensei-lms/learning-mode-footer',
	title: __( 'Footer', 'sensei-lms' ),
	description: __( 'Footer', 'sensei-lms' ),
	isDefault: true,
	attributes: {
		elementClass: 'sensei-course-theme__footer',
	},
	innerBlocks: [ row ],
};

const sidebar = {
	...shared,
	name: 'sensei-lms/sidebar',
	title: __( 'Sidebar', 'sensei-lms' ),
	description: __(
		'Fixed left or right sidebar (Desktop). Displayed as an overlay menu on Mobile. Add the "Sidebar Toggle" block to a header to allow opening and closing the overlay menu.',
		'sensei-lms'
	),
	attributes: {
		elementClass: 'sensei-course-theme__sidebar',
	},
};

const mainContent = {
	...shared,
	name: 'sensei-lms/main-content',
	title: __( 'Main Content', 'sensei-lms' ),
	description: __( 'Content Area.', 'sensei-lms' ),
	attributes: {
		elementClass: 'sensei-course-theme__main-content',
	},
};

const twoColumnLayout = {
	...shared,
	name: 'sensei-lms/main-columns',
	title: __( 'Two-column Layout', 'sensei-lms' ),
	description: __( 'Layout with a fixed sidebar', 'sensei-lms' ),
	attributes: {
		elementClass: 'sensei-course-theme__columns',
	},
	innerBlocks: [
		[
			'sensei-lms/ui',
			{
				...mainContent.attributes,
				lock: {
					remove: true,
					move: false,
				},
			},
		],
		[
			'sensei-lms/ui',
			{
				...sidebar.attributes,
				lock: {
					remove: true,
					move: false,
				},
			},
		],
	],
};

const variationMeta = {
	[ header.attributes.elementClass ]: {
		isFrame: true,
	},
	[ sidebar.attributes.elementClass ]: {
		isFrame: true,
	},
	[ twoColumnLayout.attributes.elementClass ]: {
		innerBlocksProps: {
			orientation: 'horizontal',
			allowedBlocks: [],
			renderAppender: false,
		},
		isFrame: false,
	},
	[ mainContent.attributes.elementClass ]: {
		isFrame: false,
	},
};

const variations = [ header, twoColumnLayout, mainContent, sidebar, footer ];

/**
 * Build block props from variation attributes and settings.
 *
 * @param {Object} props
 * @param {string} props.className
 * @param {string} props.elementClass
 */
const mergeVariationProps = ( { className, elementClass, ...props } ) => {
	const frameClass =
		variationMeta[ elementClass ]?.isFrame ?? true
			? 'sensei-course-theme__frame'
			: '';
	return {
		...props,
		className: classnames( frameClass, className, elementClass ),
	};
};

/**
 * User interface block for Learning mode layout elements.
 *
 * @param {string} mode  Edit|Save
 * @param {Object} props Block pros.
 */
function UiBlock( mode, props ) {
	const blockPropsFn = {
		Edit: ( blockProps, options ) =>
			useInnerBlocksProps( useBlockProps( blockProps ), options ),
		Save: ( blockProps ) =>
			useInnerBlocksProps.save( useBlockProps.save( blockProps ) ),
	}[ mode ];
	const { tagName: Tag, className, elementClass } = props.attributes;
	const { innerBlocksProps } = variationMeta[ elementClass ] ?? {};
	return (
		<Tag
			{ ...blockPropsFn(
				mergeVariationProps( { className, elementClass } ),
				innerBlocksProps
			) }
		/>
	);
}
UiBlock.Edit = UiBlock.bind( null, [ 'Edit' ] );
UiBlock.Save = UiBlock.bind( null, [ 'Save' ] );

export default {
	...meta,
	title: __( 'Interface Element', 'sensei-lms' ),
	name: 'sensei-lms/ui',
	icon: {
		src: <Icon icon={ layout } />,
		foreground: '#43AF99',
	},
	edit: UiBlock.Edit,
	save: UiBlock.Save,
	variations,
};
