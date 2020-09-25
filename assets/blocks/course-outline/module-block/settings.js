import { InspectorControls } from '@wordpress/block-editor';
import { Button, PanelBody } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { ModuleStatusControl } from './module-status-control';

/**
 * Inspector controls for module block.
 *
 * @param {Object}   props
 * @param {boolean}  props.isPreviewCompleted    Whether Completed preview is enabled.
 * @param {Function} props.setIsPreviewCompleted Callback to be called when preview is updated.
 * @param {string}   props.clientId
 * @param {string}   props.className
 */
export function ModuleBlockSettings( {
	isPreviewCompleted,
	setIsPreviewCompleted,
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

	const blockStyle =
		( className && className.match( /is-style-(\w+)/ ) ) || 'default';

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
			<div style={ { padding: '16px' } }>
				<Button isLink onClick={ applyStyle }>
					{ __( 'Apply style to all modules', 'sensei-lms' ) }
				</Button>
			</div>
		</InspectorControls>
	);
}
