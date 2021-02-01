/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import { useSelect, useDispatch } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { createContext, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import OutlinePlaceholder from './outline-placeholder';
import { COURSE_STORE } from '../store';
import { useBlocksCreator } from '../use-block-creator';
import OutlineSettings from './outline-settings';
import { withDefaultBlockStyle } from '../../../shared/blocks/settings';
import { COURSE_STATUS_STORE } from '../status-store';
import ToggleLegacyCourseMetaboxesWrapper from '../../toggle-legacy-course-metaboxes-wrapper';

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

/**
 * Edit course outline block component.
 *
 * @param {Object}   props               Component props.
 * @param {string}   props.clientId      Block client ID.
 * @param {string}   props.className     Custom class name.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Block setAttributes callback.
 */
const OutlineEdit = ( props ) => {
	const { clientId, className, attributes, setAttributes } = props;

	const { fetchCourseStructure } = useDispatch( COURSE_STORE );

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

	const content = isEmpty ? (
		<OutlinePlaceholder
			addBlock={ ( type ) => setBlocks( [ { type } ], true ) }
		/>
	) : (
		<OutlineAttributesContext.Provider
			value={ {
				outlineAttributes: attributes,
				outlineSetAttributes: setAttributes,
				outlineClassName: className,
			} }
		>
			<OutlineSettings { ...props } />

			<section className={ className }>
				<InnerBlocks
					allowedBlocks={ [
						'sensei-lms/course-outline-module',
						'sensei-lms/course-outline-lesson',
					] }
				/>
			</section>
		</OutlineAttributesContext.Provider>
	);

	return (
		<ToggleLegacyCourseMetaboxesWrapper { ...props }>
			{ content }
		</ToggleLegacyCourseMetaboxesWrapper>
	);
};

export default compose( withDefaultBlockStyle() )( OutlineEdit );
