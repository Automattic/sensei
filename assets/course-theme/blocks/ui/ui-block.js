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
import variations from './ui-block.variations';

const helpers = {
	blockPropsFn: {
		Edit: ( blockProps, options ) =>
			useInnerBlocksProps( useBlockProps( blockProps ), options ),
		Save: ( blockProps ) =>
			useInnerBlocksProps.save( useBlockProps.save( blockProps ) ),
	},
	/**
	 * Get variation by elementClass attribute.
	 *
	 * @param {string} elementClass
	 */
	getVariation: ( elementClass ) =>
		variations.find(
			( variation ) => elementClass === variation.attributes.elementClass
		),
	/**
	 * Build block props from variation attributes and settings.
	 *
	 * @param {Object} variation          Variation definition.
	 * @param {Object} props              Block props.
	 * @param {string} props.className
	 * @param {string} props.elementClass Class identifying the variation.
	 */
	mergeVariationProps: (
		variation,
		{ className, elementClass, ...props }
	) => {
		const frameClass =
			variation?.meta?.isFrame ?? true
				? 'sensei-course-theme__frame'
				: '';

		return {
			...props,
			className: classnames( frameClass, className, elementClass ),
		};
	},
};

/**
 * User interface block for Learning mode layout elements.
 *
 * @param {Function} useBlockPropsFn
 * @param {Object}   props           Block pros.
 */
const UiBlock = ( useBlockPropsFn, props ) => {
	const { tagName: Tag, className, elementClass } = props.attributes;
	const variation = helpers.getVariation( elementClass ) ?? {};
	return (
		<Tag
			{ ...useBlockPropsFn(
				helpers.mergeVariationProps( variation, {
					className,
					elementClass,
				} ),
				variation?.meta?.innerBlocksProps
			) }
		/>
	);
};

UiBlock.Edit = UiBlock.bind( null, helpers.blockPropsFn.Edit );
UiBlock.Save = UiBlock.bind( null, helpers.blockPropsFn.Save );

export default {
	...meta,
	title: __( 'Interface Element', 'sensei-lms' ),
	icon: {
		src: <Icon icon={ layout } />,
		foreground: '#43AF99',
	},
	edit: UiBlock.Edit,
	save: UiBlock.Save,
	variations,
};
