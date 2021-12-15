/**
 * WordPress dependencies
 */
import { Disabled, RadioControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Status } from '../index';

/**
 * Labels for each of the statuses.
 */
export const StatusLabels = {
	[ Status.NOT_STARTED ]: __( 'Not Started', 'sensei-lms' ),
	[ Status.IN_PROGRESS ]: __( 'In Progress', 'sensei-lms' ),
	[ Status.COMPLETED ]: __( 'Completed', 'sensei-lms' ),
};

/**
 * A component which controls the status preview for a block. It contains a group of buttons and a description.
 *
 * @param {Object}   props           Component props. Extras will be handed through to the `SelectControl` component.
 * @param {Array}    props.options   The ordered Status constants to include.
 * @param {string}   props.status    The index of the current status.
 * @param {Function} props.setStatus A callback which is called with the index when a status is selected.
 * @param {boolean}  props.disabled  Flag which disables the controls.
 */
export const StatusControl = ( {
	options = [ Status.IN_PROGRESS, Status.COMPLETED ],
	status,
	setStatus,
	disabled,
	...props
} ) => {
	const statusOptions = options.map( ( statusOption ) => ( {
		label: StatusLabels[ statusOption ],
		value: statusOption,
	} ) );

	let radioControl = (
		<RadioControl
			className="wp-block-sensei-lms-course-outline-status-control"
			help={ __(
				'Preview a status. The actual status that the student sees is determined by their progress in the course.',
				'sensei-lms'
			) }
			{ ...props }
			options={ statusOptions }
			selected={ status }
			onChange={ ( value ) => setStatus( value ) }
		/>
	);

	if ( disabled ) {
		radioControl = <Disabled>{ radioControl }</Disabled>;
	}

	return radioControl;
};
