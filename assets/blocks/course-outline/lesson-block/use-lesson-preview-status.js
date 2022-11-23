/**
 * WordPress dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { COURSE_STATUS_STORE } from '../status-preview/status-store';

/**
 * Get or set the lesson's preview status.
 *
 * @param {Object}  props
 * @param {Object}  props.attributes           Block attributes
 * @param {boolean} props.attributes.isExample Ignore for example blocks
 * @param {string}  props.attributes.title     Block title
 * @param {string}  props.clientId             Block ID
 * @return {{setPreviewStatus: Function, previewStatus: string}} Preview status and control function.
 */
export const useLessonPreviewStatus = ( {
	attributes: { isExample, title },
	clientId,
} ) => {
	const { setLessonStatus, trackLesson, ignoreLesson } = useDispatch(
		COURSE_STATUS_STORE
	);
	// If the lesson has a title and it isn't an example, add it to the tracked lessons in the status store.
	useEffect( () => {
		if ( ! isExample ) {
			if ( title.length > 0 ) {
				trackLesson( clientId );
			} else {
				ignoreLesson( clientId );
			}
		}
	}, [ clientId, trackLesson, ignoreLesson, title, isExample ] );

	const previewStatus = useSelect(
		( selectStatus ) =>
			selectStatus( COURSE_STATUS_STORE ).getLessonStatus( clientId ),
		[ clientId ]
	);

	return {
		setPreviewStatus: ( status ) => setLessonStatus( clientId, status ),
		previewStatus,
	};
};
