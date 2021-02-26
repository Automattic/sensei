/**
 * WordPress dependencies
 */
import { Path, Rect, SVG } from '@wordpress/components';

const quizIcon = (
	<SVG viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
		<Path
			fillOpacity="0"
			stroke="currentColor"
			strokeWidth="1.5"
			d="M21 8V19C21 20.1046 20.1057 21 19.0011 21C15.8975 21 9.87435 21 6 21"
		/>
		<Rect
			x="3.75"
			y="3.75"
			width="13.5"
			height="13.5"
			rx="0.875"
			stroke="currentColor"
			strokeWidth="1.5"
			fillOpacity="0"
		/>
		<Path
			fill="currentColor"
			fillRule="evenodd"
			clipRule="evenodd"
			d="M12 9C12 8.17157 11.3284 7.5 10.5 7.5C9.67157 7.5 9 8.17157 9 9H7.5C7.5 7.34315 8.84315 6 10.5 6C12.1569 6 13.5 7.34315 13.5 9C13.5 10.3981 12.5442 11.5721 11.25 11.9053V12.75H9.75V11.5C9.75 10.9291 10.2023 10.5422 10.6673 10.4908C11.4167 10.4081 12 9.77168 12 9ZM9.75 13.75V15.25H11.25V13.75H9.75Z"
		/>
	</SVG>
);

export default quizIcon;
