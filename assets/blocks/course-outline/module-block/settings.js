import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { ModuleStatusControl } from './module-status-control';
import {
	ContrastChecker,
	InspectorControls,
	PanelColorSettings,
} from '@wordpress/block-editor';
import { Button } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Inspector controls for module block.
 *
 * @param {Object}   props
 * @param {boolean}  props.isPreviewCompleted    Whether Completed preview is enabled.
 * @param {Function} props.setIsPreviewCompleted Callback to be called when preview is updated.
 */
export function ModuleBlockSettings( {
     isPreviewCompleted,
     setIsPreviewCompleted,
     mainColor,
     setMainColor,
     textColor,
     setTextColor,
     clientId,
     className,
   } ) {

	const getModuleBlocks = useSelect(
		( select ) => {
			return () =>
				select( 'core/block-editor' )
					.getBlocks( clientId )
					.filter(
						( { name } ) =>
							'sensei-lms/course-outline-module' === name
					);
		},
		[ clientId ]
	);

	const { updateBlockAttributes } = useDispatch( 'core/block-editor' );

	const blockStyle = className.match( /is-style-(\w+)/ ) || 'default';

	const applyStyle = () => {
		getModuleBlocks().forEach( ( block ) => {
			updateBlockAttributes( block.clientId, {
				className: block.className.replace(
					/(is-style-\w+|$)/,
					` is-style-${ blockStyle[ 0 ] || 'default' }`
				),
			} );
		} );
	};

	return (
		<InspectorControls>
			<PanelBody
				title={ __( 'Status', 'sensei-lms' ) }
				initialOpen={ true }
			>
				<ModuleStatusControl
					isPreviewCompleted={ isPreviewCompleted }
					setIsPreviewCompleted={ setIsPreviewCompleted }
				/>
			</PanelBody>

			<PanelColorSettings
				title={ __( 'Color settings', 'sensei-lms' ) }
				colorSettings={ [
					{
						value: mainColor.color,
						label: __( 'Main color', 'sensei-lms' ),
						onChange: setMainColor,
					},
					{
						value: textColor.color,
						label: __( 'Text color', 'sensei-lms' ),
						onChange: setTextColor,
					},
				] }
			>
				{ 'default' === blockStyle && (
					<ContrastChecker
						{ ...{
							textColor: textColor.color,
							backgroundColor: mainColor.color,
						} }
						isLargeText={ false }
					/>
				) }
				<Button isLink>
					{ __( 'Apply colors to all modules', 'sensei-lms' ) }
				</Button>
			</PanelColorSettings>
		</InspectorControls>
	);
}
