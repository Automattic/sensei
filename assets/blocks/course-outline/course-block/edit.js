import { InnerBlocks } from '@wordpress/block-editor';
import { useSelect, useDispatch } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { createContext, useEffect, useRef } from '@wordpress/element';

import { CourseOutlinePlaceholder } from './placeholder';
import { COURSE_STORE } from '../store';
import { useBlocksCreator } from '../use-block-creator';
import { OutlineBlockSettings } from './settings';
import { withDefaultBlockStyle } from '../../../shared/blocks/settings';
import { COURSE_STATUS_STORE } from '../status-store';
import { getCourseInnerBlocks } from '../get-course-inner-blocks';
import { getActiveStyleClass, applyStyleClass } from '../apply-style-class';
import useToggleLegacyMetaboxes from '../../use-toggle-legacy-metaboxes';

/**
 * A React context which contains the attributes and the setAttributes callback of the Outline block.
 */
export const OutlineAttributesContext = createContext();

/**
 * A hook to update the status store when a lesson is removed.
 *
 * @param {string}  clientId  The outline block id.
 * @param {boolean} isPreview Whether the block is currently in preview mode.
 */
const useSynchronizeLessonsOnUpdate = function ( clientId, isPreview ) {
	const outlineDescendants = useSelect(
		( select ) => {
			return select( 'core/block-editor' ).getClientIdsOfDescendants( [
				clientId,
			] );
		},
		[ clientId ]
	);

	const { stopTrackingRemovedLessons } = useDispatch( COURSE_STATUS_STORE );

	useEffect( () => {
		if ( ! isPreview ) {
			stopTrackingRemovedLessons( outlineDescendants );
		}
	}, [
		clientId,
		outlineDescendants,
		isPreview,
		stopTrackingRemovedLessons,
	] );
};

const useApplyStyleToModules = ( clientId, className, isPreview ) => {
	const oldOutlineClass = useRef( null );
	const outlineStyles = useSelect(
		( select ) =>
			select( 'core/blocks' ).getBlockStyles(
				'sensei-lms/course-outline'
			),
		[]
	);

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
};

/**
 * Edit course outline block component.
 *
 * @param {Object}   props               Component props.
 * @param {string}   props.clientId      Block client ID.
 * @param {string}   props.className     Custom class name.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Block setAttributes callback.
 */
const EditCourseOutlineBlock = ( {
	clientId,
	className,
	attributes,
	setAttributes,
} ) => {
	useToggleLegacyMetaboxes( { ignoreToggle: attributes.isPreview } );

	const { fetchCourseStructure } = useDispatch( COURSE_STORE );
	const { updateBlockAttributes } = useDispatch( 'core/block-editor' );

	useEffect( () => {
		if ( ! attributes.isPreview ) {
			fetchCourseStructure();
		}
	}, [ attributes.isPreview, fetchCourseStructure ] );

	const { setBlocks } = useBlocksCreator( clientId );

	const isEmpty = useSelect(
		( select ) =>
			! select( 'core/block-editor' ).getBlocks( clientId ).length,
		[ clientId ]
	);

	useSynchronizeLessonsOnUpdate( clientId, attributes.isPreview );
	useApplyStyleToModules( clientId, className, attributes.isPreview );

	const applyBorder = ( newValue ) => {
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

	if ( isEmpty ) {
		return (
			<CourseOutlinePlaceholder
				addBlock={ ( type ) => setBlocks( [ { type } ], true ) }
			/>
		);
	}

	return (
		<>
			<OutlineAttributesContext.Provider
				value={ {
					outlineAttributes: attributes,
					outlineSetAttributes: setAttributes,
					outlineClassName: className,
				} }
			>
				<OutlineBlockSettings
					collapsibleModules={ attributes.collapsibleModules }
					setCollapsibleModules={ ( value ) =>
						setAttributes( { collapsibleModules: value } )
					}
					moduleBorder={ attributes.moduleBorder }
					setModuleBorder={ applyBorder }
				/>

				<section className={ className }>
					<InnerBlocks
						allowedBlocks={ [
							'sensei-lms/course-outline-module',
							'sensei-lms/course-outline-lesson',
						] }
					/>
				</section>
			</OutlineAttributesContext.Provider>
		</>
	);
};

export default compose( withDefaultBlockStyle() )( EditCourseOutlineBlock );
