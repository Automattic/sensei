import { InspectorControls } from '@wordpress/block-editor';
import { BaseControl, Button, PanelBody } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { pick } from 'lodash';
import { ModuleStatusControl } from './module-status-control';

/**
 * Inspector controls for module block.
 *
 * @param {Object}   props
 * @param {boolean}  props.isPreviewCompleted    Whether Completed preview is enabled.
 * @param {Function} props.setIsPreviewCompleted Callback to be called when preview is updated.
 * @param {string}   props.clientId              Block ID.
 * @param {Object}   props.attributes            Block attributes.
 */
export const ModuleBlockSettings = ( {
	isPreviewCompleted,
	setIsPreviewCompleted,
	clientId,
	attributes,
} ) => {
	const getModuleBlocks = useSelect(
		( select ) => {
			return () => {
				const blockEditor = select( 'core/block-editor' );
				const outlineBlock = blockEditor.getBlockParentsByBlockName(
					clientId,
					'sensei-lms/course-outline'
				);

				if ( ! outlineBlock.length ) return [];

				return blockEditor
					.getBlocks( outlineBlock[ 0 ] )
					.filter(
						( { name } ) =>
							'sensei-lms/course-outline-module' === name
					);
			};
		},
		[ clientId ]
	);

	const { updateBlockAttributes } = useDispatch( 'core/block-editor' );

	const sharedAttributeNames = [
		'mainColor',
		'customMainColor',
		'textColor',
		'customTextColor',
		'className',
	];

	const applyStyle = () => {
		updateBlockAttributes(
			getModuleBlocks().map( ( block ) => block.clientId ),
			pick( attributes, sharedAttributeNames )
		);
	};

	return (
		<>
			<InspectorControls>
				<div className="sensei-block-style-append">
					<BaseControl
						help={ __(
							'Use the selected style and color settings for all modules.',
							'sensei-lms'
						) }
					>
						<Button isLink onClick={ applyStyle }>
							{ __( 'Apply style to all modules', 'sensei-lms' ) }
						</Button>
					</BaseControl>
				</div>
				<PanelBody
					title={ __( 'Status', 'sensei-lms' ) }
					initialOpen={ false }
				>
					<ModuleStatusControl
						isPreviewCompleted={ isPreviewCompleted }
						setIsPreviewCompleted={ setIsPreviewCompleted }
					/>
				</PanelBody>
			</InspectorControls>
		</>
	);
};
