/**
 * WordPress dependencies
 */
import { Circle, Path, SVG } from '@wordpress/components';

export const ListViewIcon = () => (
	<SVG viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
		<Path
			fill="currentColor"
			fillRule="evenodd"
			clipRule="evenodd"
			d="M20 5.5H4V4H20V5.5Z"
		/>
		<Path
			fill="currentColor"
			fillRule="evenodd"
			clipRule="evenodd"
			d="M20 12.5H12V11H20V12.5Z"
		/>
		<Path
			fill="currentColor"
			fillRule="evenodd"
			clipRule="evenodd"
			d="M20 20H4V18.5H20V20Z"
		/>
		<Circle fill="currentColor" cx="6" cy="12" r="2" />
	</SVG>
);
