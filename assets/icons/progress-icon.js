/**
 * WordPress dependencies
 */
import { Rect, Path, SVG } from '@wordpress/components';

export const ProgressIcon = () => (
	<SVG viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
		<Rect
			x="2.75"
			y="7.75"
			width="18.5"
			height="6.5"
			rx="3.25"
			stroke="currentColor"
			strokeWidth="1.5"
			fillOpacity="0"
		/>
		<Path
			d="M6 7.75 H 16.7 L 10.2 14.25 H 6 C 4.2 14.25 2.75 12.8 2.75 11 C 2.75 9.2 4.2 7.75 6 7.75Z"
			strokeWidth="1.5"
		/>
	</SVG>
);
