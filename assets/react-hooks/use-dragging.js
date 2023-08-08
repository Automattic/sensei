/**
 * WordPress dependencies
 */
import { useEffect, useState, useCallback } from '@wordpress/element';

/**
 * A dragging hook.
 *
 * @param {Object}   options             Hook options.
 * @param {Function} options.onDrag      Drag callback.
 * @param {Function} options.onDragStart Drag start callback.
 * @param {Function} options.onDragEnd   Drag end callback.
 *
 * @return {Object} Object containing draggable props, and isDragging.
 */
export const useDragging = ( {
	onDrag = () => {},
	onDragStart = () => {},
	onDragEnd = () => {},
} ) => {
	const [ isDragging, setIsDragging ] = useState( false );
	const [ initialPosition, setInitialPosition ] = useState( {} );

	/**
	 * Get the event properties from touch or mouse events.
	 *
	 * @param {Object} e Original event.
	 *
	 * @return {Object} Event properties.
	 */
	const getEvent = ( e ) => e.touches?.[ 0 ] || e.changedTouches?.[ 0 ] || e;

	/**
	 * Get the enhanced event, including the diff from initial position.
	 *
	 * @return {Object} Original event and dragged diff.
	 */
	const getEnhancedEvent = useCallback(
		( event ) => {
			const e = getEvent( event );

			return {
				event,
				diff: {
					x: e.clientX - initialPosition.x,
					y: e.clientY - initialPosition.y,
				},
			};
		},
		[ initialPosition ]
	);

	/**
	 * Mouse down event - Start dragging.
	 */
	const onMouseDown = useCallback(
		( event ) => {
			const e = getEvent( event );
			onDragStart();
			setIsDragging( true );
			setInitialPosition( {
				x: e.clientX,
				y: e.clientY,
			} );
		},
		[ onDragStart ]
	);

	/**
	 * Mouse up event - end dragging.
	 */
	const onMouseUp = useCallback(
		( event ) => {
			const e = getEvent( event );
			if ( isDragging ) {
				event.preventDefault();
				setIsDragging( false );
				onDragEnd( getEnhancedEvent( e ) );
			}
		},
		[ isDragging, onDragEnd, getEnhancedEvent ]
	);

	/**
	 * Mouse move event - dragging.
	 */
	const onMouseMove = useCallback(
		( event ) => {
			if ( isDragging ) {
				const e = getEvent( event );
				onDrag( getEnhancedEvent( e ) );
			}
		},
		[ isDragging, onDrag, getEnhancedEvent ]
	);

	useEffect( () => {
		/* eslint-disable @wordpress/no-global-event-listener */
		document.addEventListener( 'mouseup', onMouseUp );
		document.addEventListener( 'touchend', onMouseUp );
		document.addEventListener( 'mousemove', onMouseMove );
		document.addEventListener( 'touchmove', onMouseMove );

		return () => {
			document.removeEventListener( 'mouseup', onMouseUp );
			document.removeEventListener( 'touchend', onMouseUp );
			document.removeEventListener( 'mousemove', onMouseMove );
			document.removeEventListener( 'touchmove', onMouseMove );
		};
		/* eslint-enable */
	}, [ onMouseUp, onMouseMove ] );

	return {
		draggableProps: { onMouseDown, onTouchStart: onMouseDown },
		isDragging,
	};
};

export default useDragging;
