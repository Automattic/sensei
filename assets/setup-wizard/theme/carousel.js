/**
 * WordPress dependencies
 */
import { useState, Children } from '@wordpress/element';

const Carousel = ( { children } ) => {
	const [ activeIndex, setActiveIndex ] = useState( 0 );

	const count = Children.count( children );
	const translate = -activeIndex * 100;

	const style = {
		transform: `translateX(${ translate }%)`,
	};

	const goToPrev = () => {
		setActiveIndex( Math.max( 0, activeIndex - 1 ) );
	};

	const goToNext = () => {
		setActiveIndex( Math.min( count - 1, activeIndex + 1 ) );
	};

	return (
		<>
			<ul className="sensei-carousel">
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
