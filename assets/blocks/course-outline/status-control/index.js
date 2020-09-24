import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * An enum of Status constants.
 */
export const Statuses = {
	NOT_STARTED: 'NOT_STARTED',
	IN_PROGRESS: 'IN_PROGRESS',
	COMPLETED: 'COMPLETED',
};

/**
 * Labels for each of the statuses.
 */
export const StatusLabels = {
	[ Statuses.NOT_STARTED ]: __( 'Not Started', 'sensei-lms' ),
	[ Statuses.IN_PROGRESS ]: __( 'In Progress', 'sensei-lms' ),
	[ Statuses.COMPLETED ]: __( 'Completed', 'sensei-lms' ),
};

/**
 * A component which controls the status preview for a block. It contains a group of buttons and a description.
 *
 * @param {Object}   props                 Component props. Extras will be handed through to the `SelectControl` component.
 * @param {Array}    props.includeStatuses The ordered Status constants to include.
 * @param {string}   props.status          The index of the current status.
 * @param {Function} props.setStatus       A callback which is called with the index when a status is selected.
 */
export const StatusControl = ( {
	includeStatuses = [ Statuses.IN_PROGRESS, Statuses.COMPLETED ],
	status,
	setStatus,
	...props
} ) => {
	const statusOptions = includeStatuses.map( ( statusOption ) => ( {
		label: StatusLabels[ statusOption ],
		value: statusOption,
	} ) );

	return (
		<SelectControl
			help={ __(
				'Preview a status. The actual status that the learner sees is determined by their progress in the course.',
				'sensei-lms'
			) }
			{ ...props }
			options={ statusOptions }
			value={ status }
			onChange={ ( value ) => setStatus( value ) }
		/>
	);
};
