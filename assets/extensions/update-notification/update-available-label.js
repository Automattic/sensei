/**
 * WordPress dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Update available label component.
 *
 * @param {Object} props              Component props.
 * @param {number} props.updatesCount Number of extension updates.
 */
const updateAvailableLabel = ( { updatesCount } ) => (
	<>
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
	</>
);

export default updateAvailableLabel;
