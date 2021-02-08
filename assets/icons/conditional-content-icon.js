/**
 * WordPress dependencies
 */
import { Path, SVG } from '@wordpress/components';

const restrictedContent = (
	<SVG viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
		<Path d="M5.5 18V6a.5.5 0 01.5-.5h4.5V4H6a2 2 0 00-2 2v12a2 2 0 002 2h4.5v-1.5H6a.5.5 0 01-.5-.5zm11-14v1.5H15V4h1.5zM20 6a2 2 0 00-2-2v1.5a.5.5 0 01.5.5H20zm-1.5 10.5v-2H20v2h-1.5zM20 13h-1.5v-2H20v2zm-2 5.5a.5.5 0 00.5-.5H20a2 2 0 01-2 2v-1.5zM16.5 20v-1.5H15V20h1.5zm2-10.5H20v-2h-1.5v2zm-5-5.5v1.5H12V4h1.5zm0 14.5V20H12v-1.5h1.5z" />
	</SVG>
);

export default restrictedContent;
