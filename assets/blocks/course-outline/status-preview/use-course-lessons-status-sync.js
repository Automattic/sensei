/**
 * WordPress dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { COURSE_STATUS_STORE } from './status-store';

/**
 * A hook to update the status store when a lesson is removed.
 *
 * @param {string}  clientId  The outline block id.
 * @param {boolean} isPreview Whether the block is currently in preview mode.
 */
export const useCourseLessonsStatusSync = function ( clientId, isPreview ) {
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
