const Carousel = ( { children } ) => (
	<ul className="sensei-carousel">
		<div className="sensei-carousel__slider">{ children }</div>
	</ul>
);

Carousel.Item = ( { children } ) => <li>{ children }</li>;

export default Carousel;
