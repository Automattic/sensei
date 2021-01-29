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
 * Take course button block.
 */
export default createButtonBlockType( {
	tagName: 'a',
	EditWrapper: ToggleLegacyCourseMetaboxesWrapper,
	settings: {
		name: 'sensei-lms/button-contact-teacher',
		description: __(
			'Enable a registered user to contact the teacher. This block is only displayed if the user is logged in and private messaging is enabled.',
			'sensei-lms'
		),
		title: __( 'Contact Teacher', 'sensei-lms' ),
		attributes: {
			text: {
				default: __( 'Contact Teacher', 'sensei-lms' ),
			},
		},
		styles: [
			BlockStyles.Fill,
			{ ...BlockStyles.Outline, isDefault: true },
			BlockStyles.Link,
		],
	},
} );
