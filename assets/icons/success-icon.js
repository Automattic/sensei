/**
 * WordPress dependencies
 */
import { Path, SVG, Circle } from '@wordpress/components';

export const SuccessIcon = () => (
	<SVG
		viewBox="0 0 24 24"
		width="24"
		height="24"
		fill="none"
		xmlns="http://www.w3.org/2000/svg"
		preserveAspectRatio
	>
		<Circle cx="21" cy="21" r="20.25" stroke="#30968B" strokeWidth="1.5" />
		<Path
			d="M27.1135 15.1507L18.8804 26.2233L14.1064 22.6735"
			stroke="#30968B"
			strokeWidth="2"
		/>
	</SVG>
);
