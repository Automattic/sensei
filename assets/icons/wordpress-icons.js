/**
 * WordPress dependencies
 */
import { Path, SVG } from '@wordpress/components';

/**
 * The following components are copied from @wordpress/icons since the package is not available on WP 5.3. We should
 * remove them once we stop supporting 5.3.
 */
export const alert = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
		<Path d="M13 7h-2v6h2V7zM13 15h-2v2h2v-2z" />
		<Path d="M12 4.75a7.25 7.25 0 100 14.5 7.25 7.25 0 000-14.5zM3.25 12a8.75 8.75 0 1117.5 0 8.75 8.75 0 01-17.5 0z" />
	</SVG>
);
