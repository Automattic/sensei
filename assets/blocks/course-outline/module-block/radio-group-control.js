import { ButtonGroup, Button, BaseControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * A component which controls the module status preview. Is contains a group of radio buttons and a description.
 *
 * @param {Object}   props                  Component props.
 * @param {string}   props.previewStatus    The radio button that is selected.
 * @param {Function} props.setPreviewStatus A callback which is called when a new option is selected.
 */
export const RadioGroupControl = ( { previewStatus, setPreviewStatus } ) => {
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
				checked={ previewStatus }
			>
				<Button
					isPrimary={ 'in-progress' === previewStatus }
					onClick={ () => setPreviewStatus( 'in-progress' ) }
				>
					{ __( 'In Progress', 'sensei-lms' ) }
				</Button>
				<Button
					isPrimary={ 'completed' === previewStatus }
					onClick={ () => setPreviewStatus( 'completed' ) }
				>
					{ __( 'Completed', 'sensei-lms' ) }
				</Button>
			</ButtonGroup>
		</BaseControl>
	);
};
