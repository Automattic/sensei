import { InspectorControls } from '@wordpress/block-editor';
import { ExternalLink, FontSizePicker, PanelBody } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

import { Status, StatusControl } from '../status-control';

/**
 * Inspector controls for lesson block.
 *
 * @param {Object}   props                     Component props.
 * @param {string}   props.previewStatus       Status to preview.
 * @param {Function} props.setPreviewStatus    Set status to preview.
 * @param {Function} props.setAttributes       Callback method to set the lesson title font size.
 * @param {Function} props.attributes          The block attributes.
 * @param {number}   props.attributes.id       The lesson id.
 * @param {string}   props.attributes.fontSize The lesson block font size.
 */
export const LessonBlockSettings = ( {
	previewStatus,
	setPreviewStatus,
	setAttributes,
	attributes: { id, fontSize },
} ) => {
	const { fontSizes } = useSelect( ( select ) =>
		select( 'core/block-editor' ).getSettings()
	);

	return (
		<InspectorControls>
			{ id && (
				<PanelBody title={ __( 'Lesson', 'sensei-lms' ) }>
					<h2>
						<ExternalLink
							href={ `post.php?post=${ id }&action=edit` }
							target="lesson"
							className="wp-block-sensei-lms-course-outline-lesson__edit"
						>
							{ __( 'Edit lesson', 'sensei-lms' ) }
						</ExternalLink>
					</h2>
					<p>
						{ __(
							'Edit details such as lesson content, prerequisite, quiz settings and more.',
							'sensei-lms'
						) }
					</p>
				</PanelBody>
			) }
			<PanelBody title={ __( 'Typography', 'sensei-lms' ) }>
				<FontSizePicker
					fontSizes={ fontSizes }
					value={ fontSize }
					onChange={ ( value ) => {
						setAttributes( { fontSize: value } );
					} }
				/>
			</PanelBody>
			<PanelBody
				title={ __( 'Status', 'sensei-lms' ) }
				initialOpen={ false }
			>
				<StatusControl
					status={ previewStatus }
					setStatus={ setPreviewStatus }
					options={ [ Status.NOT_STARTED, Status.COMPLETED ] }
				/>
			</PanelBody>
		</InspectorControls>
	);
};
