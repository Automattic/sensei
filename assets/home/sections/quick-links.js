/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '../section';

/**
 * Quick Links section component.
 */
const QuickLinks = () => (
	<Section title={ __( 'Quick Links', 'sensei-lms' ) }>
		<ul>
			<li>Test</li>
		</ul>
	</Section>
);

export default QuickLinks;
