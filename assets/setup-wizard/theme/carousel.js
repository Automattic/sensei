/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { useState, useEffect, Children } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useDragging } from '../../react-hooks';
import ChevronLeft from '../../icons/chevron-left.svg';
import ChevronRight from '../../icons/chevron-right.svg';

/**
 * Carousel component.
 *
 * @param {Object} props          Component props.
 * @param {Object} props.children Component children.
 */
const Carousel = ( { children } ) => {
	const [ activeIndex, setActiveIndex ] = useState( 0 );
	const [ translateX, setTranslateX ] = useState( 0 );
	const count = Children.count( children );

	/**
	 * Go to previous slide.
	 *
	 * @return {boolean} Whether moved to the previous slide.
	 */
	const goToPrev = () => {
		if ( activeIndex === 0 ) {
			return false;
		}

		setActiveIndex( activeIndex - 1 );
		return true;
	};

	/**
	 * Go to next slide.
	 *
	 * @return {boolean} Whether moved to the next slide.
	 */
	const goToNext = () => {
		if ( activeIndex === count - 1 ) {
			return false;
		}

		setActiveIndex( activeIndex + 1 );
		return true;
	};

	/**
	 * Get translate X related to the index.
	 *
	 * @param {number} index Index.
	 *
	 * @return {number} Translate X calculated.
	 */
	const getIndexTranslate = ( index ) => -index * 100;

	/**
	 * Drag event.
	 *
	 * @param {Object} arg       Drag argument.
	 * @param {Object} arg.event Drag event.
	 * @param {Object} arg.diff  Drag diff.
	 */
	const onDrag = ( { event: e, diff } ) => {
		const elementWidth = e.target.getBoundingClientRect().width;

		// Update translate based on the partial drag.
		setTranslateX(
			getIndexTranslate( activeIndex ) + ( diff.x / elementWidth ) * 100
		);
	};

	/**
	 * Drag end event.
	 *
	 * @param {Object} arg       Drag argument.
	 * @param {Object} arg.event Drag event.
	 * @param {Object} arg.diff  Drag diff.
	 */
	const onDragEnd = ( { event: e, diff } ) => {
		const elementWidth = e.target.getBoundingClientRect().width;
		const percentageMoved = ( diff.x / elementWidth ) * 100;

		if ( Math.abs( percentageMoved ) <= 50 ) {
			// If didn't drag enough, reset the current index translate.
			setTranslateX( getIndexTranslate( activeIndex ) );
		} else if ( percentageMoved > 50 ) {
			if ( ! goToPrev() ) {
				// If didn't move, reset the current index translate.
				setTranslateX( getIndexTranslate( activeIndex ) );
			}
		} else if ( percentageMoved < -50 ) {
			if ( ! goToNext() ) {
				// If didn't move, reset the current index translate.
				setTranslateX( getIndexTranslate( activeIndex ) );
			}
		}
	};

	const { draggableProps, isDragging } = useDragging( {
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
		<div
			className={ classnames( 'sensei-carousel', {
				'sensei-carousel--is-dragging': isDragging,
			} ) }
		>
			<div { ...draggableProps } className="sensei-carousel__viewport">
				<ul className="sensei-carousel__slider" style={ style }>
					{ children }
				</ul>
			</div>

			<div className="sensei-carousel__controls">
				<button
					className="sensei-carousel__control sensei-carousel__control--prev"
					onClick={ goToPrev }
					title={ __( 'Previous', 'sensei-lms' ) }
					disabled={ activeIndex === 0 }
				>
					<ChevronLeft />
				</button>
				<button
					className="sensei-carousel__control sensei-carousel__control--next"
					onClick={ goToNext }
					title={ __( 'Next', 'sensei-lms' ) }
					disabled={ activeIndex === count - 1 }
				>
					<ChevronRight />
				</button>
			</div>
		</div>
	);
};

Carousel.Item = ( { children } ) => <li>{ children }</li>;

export default Carousel;
