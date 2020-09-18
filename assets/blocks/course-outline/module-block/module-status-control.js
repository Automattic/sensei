import { ButtonGroup, Button, BaseControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * A component which controls the module status preview. Is contains a group of buttons and a description.
 *
 * @param {Object}   props                       Component props.
 * @param {boolean}  props.isPreviewCompleted    True if the 'Completed' preview is enabled.
 * @param {Function} props.setIsPreviewCompleted A callback which is called when a button is clicked.
 */
export const ModuleStatusControl = ( {
	isPreviewCompleted,
	setIsPreviewCompleted,
} ) => {
	const id = 'inspector-module-status-control';

	return (
		<BaseControl
			id={ id }
			help={ __(
				'Preview a module status. The actual status that the learner sees is determined by their progress in the course.',
				'sensei-lms'
			) }
		>
			<ButtonGroup
				aria-label={ __( 'Status preview', 'sensei-lms' ) }
				aria-describedby={ id + '__help' }
			>
				<Button
					isPrimary={ ! isPreviewCompleted }
					onClick={ () => setIsPreviewCompleted( false ) }
				>
					{ __( 'In Progress', 'sensei-lms' ) }
				</Button>
				<Button
					isPrimary={ isPreviewCompleted }
					onClick={ () => setIsPreviewCompleted( true ) }
				>
					{ __( 'Completed', 'sensei-lms' ) }
				</Button>
			</ButtonGroup>
		</BaseControl>
	);
};
