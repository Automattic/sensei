import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Inspector controls for lesson actions block.
 *
 * @param {Object}   props
 * @param {boolean}  props.resetLessonOn     Whether reset lesson is enabled.
 * @param {Function} props.toggleResetLesson Toggle reset lesson.
 */
export const LessonActionsBlockSettings = ( {
	resetLessonOn,
	toggleResetLesson,
} ) => (
	<InspectorControls>
		<PanelBody title={ __( 'Additional Actions', 'sensei-lms' ) }>
			<ToggleControl
				checked={ resetLessonOn }
				onChange={ toggleResetLesson }
				label={ __( 'Reset lesson', 'sensei-lms' ) }
			/>
		</PanelBody>
	</InspectorControls>
);
