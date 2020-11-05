import { InnerBlocks } from '@wordpress/block-editor';
import { useSelect, withSelect, dispatch } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { createContext, useEffect, useState } from '@wordpress/element';

import { CourseOutlinePlaceholder } from './placeholder';
import { COURSE_STORE } from '../store';
import { useBlocksCreator } from '../use-block-creator';
import { OutlineBlockSettings } from './settings';
import { withDefaultBlockStyle } from '../../../shared/blocks/settings';
import { COURSE_STATUS_STORE } from '../status-store';
import { getCourseInnerBlocks } from '../get-course-inner-blocks';
import { getActiveStyleClass, applyStyleClass } from '../apply-style-class';

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

	useEffect( () => {
		if ( ! isPreview ) {
			dispatch( COURSE_STATUS_STORE ).stopTrackingRemovedLessons(
				outlineDescendants
			);
		}
	}, [ clientId, outlineDescendants, isPreview ] );
};

const useApplyStyleToModules = ( clientId, className, isPreview ) => {
	const [ oldOutlineClass, setOutlineClass ] = useState( null );
	const outlineStyles = useSelect(
		( select ) =>
			select( 'core/blocks' ).getBlockStyles(
				'sensei-lms/course-outline'
			),
		[]
	);

	const newOutlineClass = getActiveStyleClass( outlineStyles, className );

	// setOutlineClass is called when there is an update in course style class only.
	// eslint-disable-next-line react-hooks/exhaustive-deps
	useEffect( () => {
		if ( isPreview ) {
			return;
		}

		if ( newOutlineClass && oldOutlineClass !== newOutlineClass ) {
			setOutlineClass( newOutlineClass );

			if ( ! oldOutlineClass ) {
				return;
			}

			getCourseInnerBlocks(
				clientId,
				'sensei-lms/course-outline-module'
			).forEach( ( module ) =>
				applyStyleClass( module.clientId, newOutlineClass )
			);
		}
	} );
};

/**
 * Edit course outline block component.
 *
 * @param {Object}   props               Component props.
 * @param {string}   props.clientId      Block client ID.
 * @param {string}   props.className     Custom class name.
 * @param {Object[]} props.structure     Course module and lesson blocks.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Block setAttributes callback.
 */
const EditCourseOutlineBlock = ( {
	clientId,
	className,
	structure,
	attributes,
	setAttributes,
} ) => {
	// Toggle legacy metaboxes.
	useEffect( () => {
		if ( attributes.isPreview ) return;
		window.sensei_toggleLegacyMetaboxes( false );

		return () => {
			window.sensei_toggleLegacyMetaboxes( true );
		};
	}, [ attributes.isPreview ] );

	const { setBlocks } = useBlocksCreator( clientId );

	const isEmpty = useSelect(
		( select ) =>
			! select( 'core/block-editor' ).getBlocks( clientId ).length,
		[ clientId, structure ]
	);

	useEffect( () => {
		if ( structure?.length && ! attributes.isPreview ) {
			setBlocks( structure );
		}
	}, [ structure, setBlocks, attributes.isPreview ] );

	useSynchronizeLessonsOnUpdate( clientId, attributes.isPreview );
	useApplyStyleToModules( clientId, className, attributes.isPreview );

	const applyBorder = ( newValue ) => {
		const modules = getCourseInnerBlocks(
			clientId,
			'sensei-lms/course-outline-module'
		);

		modules.forEach( ( module ) => {
			dispatch( 'core/block-editor' ).updateBlockAttributes(
				module.clientId,
				{
					bordered: newValue,
				}
			);
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

const selectors = ( select ) => ( {
	structure: select( COURSE_STORE ).getStructure(),
} );

export default compose(
	withSelect( selectors ),
	withDefaultBlockStyle()
)( EditCourseOutlineBlock );
