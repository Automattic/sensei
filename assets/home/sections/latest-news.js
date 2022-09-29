/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '../section';

/**
 * Latest News section component.
 */
const LatestNews = () => (
	<Section title={ __( 'Latest News', 'sensei-lms' ) }>
		<ul>
			<li>Test 4</li>
		</ul>
	</Section>
);

export default LatestNews;
