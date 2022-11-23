/**
 * WordPress dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { applyStyleClass, getActiveStyleClass } from '../apply-style-class';
import { getCourseInnerBlocks } from '../data';

/**
 * Use shared module style and module border settings.
 *
 * @param {Object}   props
 * @param {string}   props.clientId                Outline block Id.
 * @param {string}   props.className               Outline block classes.
 * @param {boolean}  props.isPreview               Skip if it's a block preview.
 * @param {Object}   props.attributes              Outline block attributes.
 * @param {boolean}  props.attributes.moduleBorder Shared module border setting.
 * @param {Function} props.setAttributes           Update outline block attributes.
 * @return {{moduleBorder, setModuleBorder}} Module border setting and setter.
 */
export const useSharedModuleStyles = ( {
	clientId,
	className,
	isPreview,
	attributes: { moduleBorder },
	setAttributes,
} ) => {
	const oldOutlineClass = useRef( null );
	const outlineStyles = useSelect(
		( select ) =>
			select( 'core/blocks' ).getBlockStyles(
				'sensei-lms/course-outline'
			),
		[]
	);

	const { updateBlockAttributes } = useDispatch( 'core/block-editor' );

	const newOutlineClass = getActiveStyleClass( outlineStyles, className );

	useEffect( () => {
		if ( isPreview ) {
			return;
		}

		if ( newOutlineClass && oldOutlineClass.current !== newOutlineClass ) {
			if ( ! oldOutlineClass.current ) {
				oldOutlineClass.current = newOutlineClass;
				return;
			}

			oldOutlineClass.current = newOutlineClass;
			getCourseInnerBlocks(
				clientId,
				'sensei-lms/course-outline-module'
			).forEach( ( module ) =>
				applyStyleClass( module.clientId, newOutlineClass )
			);
		}
	}, [ clientId, isPreview, newOutlineClass, oldOutlineClass ] );

	const setModuleBorder = ( newValue ) => {
		const modules = getCourseInnerBlocks(
			clientId,
			'sensei-lms/course-outline-module'
		);

		modules.forEach( ( module ) => {
			updateBlockAttributes( module.clientId, {
				borderedSelected: newValue,
			} );
		} );

		setAttributes( { moduleBorder: newValue } );
	};

	return { moduleBorder, setModuleBorder };
};
