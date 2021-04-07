/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { BlockStyles, createButtonBlockType } from '../button';
import ToggleLegacyCourseMetaboxesWrapper from '../toggle-legacy-course-metaboxes-wrapper';

/**
 * View results button block.
 */
export default createButtonBlockType( {
	tagName: 'a',
	EditWrapper: ToggleLegacyCourseMetaboxesWrapper,
	settings: {
		name: 'sensei-lms/button-view-results',
		description: __(
			'Allow an enrolled user to navigate to the course results page. The block is only displayed if the user is enrolled to the course.',
			'sensei-lms'
		),
		title: __( 'View Results', 'sensei-lms' ),
		attributes: {
			text: {
				default: __( 'View Results', 'sensei-lms' ),
			},
		},
		styles: [
			BlockStyles.Fill,
			{ ...BlockStyles.Outline, isDefault: true },
			BlockStyles.Link,
		],
	},
} );
