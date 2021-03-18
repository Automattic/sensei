/**
 * WordPress dependencies
 */
import { Path, SVG } from '@wordpress/components';

/**
 * The following components are copied from @wordpress/icons since the package is not available on WP 5.3. We should
 * remove them once we stop supporting 5.3.
 */
export const chevronRight = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
		<Path d="M10.6 6L9.4 7l4.6 5-4.6 5 1.2 1 5.4-6z" />
	</SVG>
);

export const chevronUp = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
		<Path d="M6.5 12.4L12 8l5.5 4.4-.9 1.2L12 10l-4.5 3.6-1-1.2z" />
	</SVG>
);

export const checked = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
		<Path d="M9 18.6L3.5 13l1-1L9 16.4l9.5-9.9 1 1z" />
	</SVG>
);

export const button = (
	<SVG viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
		<Path d="M19 6.5H5c-1.1 0-2 .9-2 2v7c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-7c0-1.1-.9-2-2-2zm.5 9c0 .3-.2.5-.5.5H5c-.3 0-.5-.2-.5-.5v-7c0-.3.2-.5.5-.5h14c.3 0 .5.2.5.5v7zM8 13h8v-1.5H8V13z" />
	</SVG>
);

export const alert = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
		<Path d="M13 7h-2v6h2V7zM13 15h-2v2h2v-2z" />
		<Path d="M12 4.75a7.25 7.25 0 100 14.5 7.25 7.25 0 000-14.5zM3.25 12a8.75 8.75 0 1117.5 0 8.75 8.75 0 01-17.5 0z" />
	</SVG>
);
