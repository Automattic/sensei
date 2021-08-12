/**
 * WordPress dependencies
 */
import { Rect, Path, SVG } from '@wordpress/components';

export const LearnerCoursesIcon = () => (
	<SVG viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
		<Rect
			stroke="currentColor"
			fill="transparent"
			x="-0.75"
			y="-0.75"
			width="10"
			height="14.5"
			transform="matrix(4.37114e-08 -1 -1 -4.37114e-08 18.5 14)"
			strokeWidth="1.5"
		/>
		<Path
			fill="currentColor"
			fillRule="evenodd"
			clipRule="evenodd"
			d="M8 12L16 12L16 8L8 8L8 12Z"
		/>
		<Path
			fill="currentColor"
			fillRule="evenodd"
			clipRule="evenodd"
			d="M7.2215 19.8616L10.7791 14.8707L9.55762 14L6.00005 18.9909L7.2215 19.8616Z"
		/>
		<Path
			fill="currentColor"
			fillRule="evenodd"
			clipRule="evenodd"
			d="M16.5578 19.8616L13.0002 14.8707L14.2217 14L17.7792 18.9909L16.5578 19.8616Z"
		/>
	</SVG>
);
