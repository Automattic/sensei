import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Inspector controls for lesson actions block.
 *
 * @param {Object}   props
 * @param {boolean}  props.resetLesson    Whether reset lesson is enabled.
 * @param {Function} props.setResetLesson Set reset lesson attribute.
 */
export function LessonActionsBlockSettings( { resetLesson, setResetLesson } ) {
	return (
		<InspectorControls>
			<PanelBody title={ __( 'Additional Actions', 'sensei-lms' ) }>
				<ToggleControl
					checked={ resetLesson }
					onChange={ setResetLesson }
					label={ __( 'Reset lesson', 'sensei-lms' ) }
				/>
			</PanelBody>
		</InspectorControls>
	);
}
