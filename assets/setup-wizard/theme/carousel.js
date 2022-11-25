/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { useState, useEffect, Children } from '@wordpress/element';

/**
 * Draggable React hook.
 *
 * @param {Object}   options           Hook options.
 * @param {Function} options.onDrag    Callback for drag event.
 * @param {Function} options.onDragEnd Callback for drag end event.
 *
 * @return {Object} Object containing draggableProps to be added to the
 *                  draggable element and isDragging state.
 */
const useDraggable = ( { onDrag, onDragEnd } ) => {
	const [ isDragging, setIsDragging ] = useState( false );
	const [ initialPosition, setInitialPosition ] = useState( { x: 0, y: 0 } );
	const [ diff, setDiff ] = useState( { x: 0, y: 0 } );

	const setDiffFromEvent = ( e ) => {
		setDiff( {
			x: e.clientX - initialPosition.x,
			y: e.clientY - initialPosition.y,
		} );
	};

	return {
		draggableProps: {
			draggable: 'true',
			onDragStart: ( e ) => {
				const img = document.createElement( 'span' );
				img.style.display = 'none';
				document.body.appendChild( img );
				e.dataTransfer.setDragImage( img, 0, 0 );
				e.dataTransfer.effectAllowed = 'move';

				setInitialPosition( { x: e.clientX, y: e.clientY } );
				setIsDragging( true );
			},
			onDrag: ( e ) => {
				e.preventDefault();
				setDiffFromEvent( e );
				onDrag( { event: e, diff } );
			},
			onDragEnd: ( e ) => {
				e.preventDefault();
				setDiffFromEvent( e );
				setIsDragging( false );
				onDragEnd( { event: e, diff } );
			},
			// Not dropping over another element.
			onDragOver: ( e ) => {
				e.preventDefault();
			},
		},
		isDragging,
	};
};

const Carousel = ( { children } ) => {
	const [ activeIndex, setActiveIndex ] = useState( 0 );
	const [ translateX, setTranslateX ] = useState( false );
	const count = Children.count( children );

	const goToPrev = () => {
		if ( activeIndex === 0 ) {
			return false;
		}

		setActiveIndex( activeIndex - 1 );
		return true;
	};

	const goToNext = () => {
		if ( activeIndex === count - 1 ) {
			return false;
		}

		setActiveIndex( activeIndex + 1 );
		return true;
	};

	const getIndexTranslate = ( i ) => -i * 100;

	const onDrag = ( { event: e, diff } ) => {
		const elementWidth = e.target.getBoundingClientRect().width;

		// Update translate based on the partial drag.
		setTranslateX(
			getIndexTranslate( activeIndex ) + ( diff.x / elementWidth ) * 100
		);
	};

	const onDragEnd = ( { event: e, diff } ) => {
		const elementWidth = e.target.getBoundingClientRect().width;
		const percentageMoved = ( diff.x / elementWidth ) * 100;

		// If didn't drag enough, reset the current index translate.
		if ( Math.abs( percentageMoved ) <= 50 ) {
			setTranslateX( getIndexTranslate( activeIndex ) );
		} else if ( percentageMoved > 50 ) {
			if ( ! goToPrev() ) {
				setTranslateX( getIndexTranslate( activeIndex ) );
			}
		} else if ( percentageMoved < -50 ) {
			if ( ! goToNext() ) {
				setTranslateX( getIndexTranslate( activeIndex ) );
			}
		}
	};

	const { draggableProps, isDragging } = useDraggable( {
		onDrag,
		onDragEnd,
	} );

	useEffect( () => {
		setTranslateX( getIndexTranslate( activeIndex ) );
	}, [ activeIndex ] );

	const style = {
		transform: `translateX(${ translateX }%)`,
	};

	return (
		<>
			<ul
				{ ...draggableProps }
				className={ classnames( 'sensei-carousel', {
					'sensei-carousel--is-dragging': isDragging,
				} ) }
			>
				<div className="sensei-carousel__slider" style={ style }>
					{ children }
				</div>
			</ul>

			<div className="sensei-carousel__controls">
				<button
					className="sensei-carousel__control sensei-carousel__control--prev"
					onClick={ goToPrev }
				>
					Prev
				</button>
				<button
					className="sensei-carousel__control sensei-carousel__control--next"
					onClick={ goToNext }
				>
					Next
				</button>
			</div>
		</>
	);
};

Carousel.Item = ( { children } ) => <li>{ children }</li>;

export default Carousel;
