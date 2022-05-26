/**
 * WordPress dependencies
 */
import { InspectorControls, BlockControls } from '@wordpress/block-editor';
import { FontSizePicker, PanelBody } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Status } from '../status-preview';
import { StatusControl } from '../status-preview/status-control';
import LessonEditToolbar, { EditLessonLink } from './lesson-edit-toolbar';

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
 * @param {string}   props.attributes.title    The lesson title.
 */
const LessonSettings = ( {
	previewStatus,
	setPreviewStatus,
	setAttributes,
	attributes: { id, fontSize, title },
} ) => {
	const { fontSizes } = useSelect( ( select ) =>
		select( 'core/block-editor' ).getSettings()
	);

	return (
		<>
			<InspectorControls>
				{ id && (
					<PanelBody title={ __( 'Lesson', 'sensei-lms' ) }>
						<h2>{ <EditLessonLink lessonId={ id } /> }</h2>
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
						disabled={ ! title }
					/>
				</PanelBody>
			</InspectorControls>
			<BlockControls>
				<LessonEditToolbar lessonId={ id } lessonTitle={ title } />
			</BlockControls>
		</>
	);
};

export default LessonSettings;
