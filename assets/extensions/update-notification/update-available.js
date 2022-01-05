/**
 * WordPress dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import UpdateIcon from '../../icons/update.svg';

/**
 * Update available label component.
 *
 * @param {Object} props              Component props.
 * @param {number} props.updatesCount Number of extension updates.
 */
const UpdateAvailable = ( { updatesCount } ) => (
	<small className="sensei-extensions__update-badge">
		<UpdateIcon />

		{ 1 === updatesCount
			? __( 'Update available', 'sensei-lms' )
			: sprintf(
					// translators: placeholder is number of updates available.
					_n(
						'%d update available',
						'%d updates available',
						updatesCount,
						'sensei-lms'
					),
					updatesCount
			  ) }
	</small>
);

export default UpdateAvailable;
