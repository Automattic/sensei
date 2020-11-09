import { InnerBlocks } from '@wordpress/block-editor';
import { useSelect, withSelect, dispatch } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { createContext, useEffect } from '@wordpress/element';

import { CourseOutlinePlaceholder } from './placeholder';
import { COURSE_STORE } from '../store';
import { useBlocksCreator } from '../use-block-creator';
import { OutlineBlockSettings } from './settings';
import { __ } from '@wordpress/i18n';
import {
	withColorSettings,
	withDefaultBlockStyle,
} from '../../../shared/blocks/settings';
import { COURSE_STATUS_STORE } from '../status-store';

/**
 * A React context which contains the attributes and the setAttributes callback of the Outline block.
 */
export const OutlineAttributesContext = createContext();

/**
 * A hook to update the status store when a lesson is removed.
 *
 * @param {string} clientId The outline block id.
 */
const useSynchronizeLessonsOnUpdate = function ( clientId ) {
	const outlineDescendants = useSelect(
		( select ) => {
			return select( 'core/block-editor' ).getClientIdsOfDescendants( [
				clientId,
			] );
		},
		[ clientId ]
	);

	useEffect( () => {
		dispatch( COURSE_STATUS_STORE ).stopTrackingRemovedLessons(
			outlineDescendants
		);
	}, [ clientId, outlineDescendants ] );
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
 * @param {Object}   props.borderColor   Border color.
 */
const EditCourseOutlineBlock = ( {
	clientId,
	className,
	structure,
	attributes,
	setAttributes,
	borderColor,
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

	useSynchronizeLessonsOnUpdate( clientId );

	const getModules = useSelect(
		( select ) => {
			return () => {
				let allChildren = select( 'core/block-editor' ).getBlocks(
					clientId
				);

				allChildren = allChildren.reduce(
					( m, block ) => [ ...m, ...block.innerBlocks ],
					allChildren
				);

				return allChildren.filter(
					( { name } ) => 'sensei-lms/course-outline-module' === name
				);
			};
		},
		[ clientId ]
	);

	const applyBorder = ( newValue ) => {
		const modules = getModules();

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

				<section
					className={ className }
					style={ { borderColor: borderColor.color } }
				>
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
	withColorSettings( {
		borderColor: {
			style: 'border-color',
			label: __( 'Border color', 'sensei-lms' ),
		},
	} ),
	withDefaultBlockStyle()
)( EditCourseOutlineBlock );
