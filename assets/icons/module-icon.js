/**
 * WordPress dependencies
 */
import { Path, SVG } from '@wordpress/components';

export const ModuleIcon = () => (
	<SVG
		width="24"
		height="24"
		viewBox="0 0 24 24"
		fill="none"
		xmlns="http://www.w3.org/2000/svg"
	>
		<Path
			fillRule="evenodd"
			clipRule="evenodd"
			d="M18.5 5.5v9h-13v-9h13zM20 16H4V4h16v12z"
		/>
		<Path
			fillRule="evenodd"
			clipRule="evenodd"
			d="M9 12h6V8H9v4zM6 20h2.222L11 16H8.778L6 20zM18 20h-2.222L13 16h2.222L18 20z"
		/>
	</SVG>
);
