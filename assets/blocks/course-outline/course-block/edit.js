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
 * A hook to update the status store when a lesson with a title is removed or created.
 *
 * @param {string} clientId The outline block id.
 */
const useUpdateLessonCount = function ( clientId ) {
	const getFilledLessons = ( filledLessons, block ) => {
		if (
			'sensei-lms/course-outline-module' === block.name &&
			block.innerBlocks.length > 0
		) {
			return block.innerBlocks.reduce( getFilledLessons, filledLessons );
		} else if (
			'sensei-lms/course-outline-lesson' === block.name &&
			block.attributes?.title
		) {
			filledLessons.push( block.clientId );
		}

		return filledLessons;
	};

	const blocks = useSelect(
		( select ) => {
			return select( 'core/block-editor' ).getBlocks( clientId );
		},
		[ clientId ]
	);

	const filledLessons = blocks.reduce( getFilledLessons, [] );

	// We want to update the status store when the array of lessons with a title is updated. Currently there is no
	// single operation which updates the array while keeping the number of its elements the same, so we add the array
	// length as a dependency to useEffect.
	useEffect( () => {
		dispatch( COURSE_STATUS_STORE ).refreshStructure(
			clientId,
			filledLessons
		);
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ clientId, filledLessons.length ] );
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

	useUpdateLessonCount( clientId );

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
