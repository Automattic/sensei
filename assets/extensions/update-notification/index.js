/**
 * WordPress dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { Icon } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Single from './single';
import Multiple from './multiple';
import { Col } from '../grid';
import updateIcon from '../../icons/update-icon';

/**
 * Update notification component.
 *
 * @param {Object} props            Component props.
 * @param {Array}  props.extensions Extensions list.
 */
const UpdateNotification = ( { extensions } ) => {
	const extensionsWithUpdate = extensions.filter(
		( extension ) => extension.canUpdate
	);

	const updatesCount = extensionsWithUpdate.length;

	if ( 0 === updatesCount ) {
		return null;
	}

	const updateAvailableLabel =
		1 === updatesCount
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
			  );

	return (
		<Col as="section" className="sensei-extensions__section" cols={ 12 }>
			<div
				role="alert"
				className="sensei-extensions__update-notification"
			>
				<small className="sensei-extensions__update-badge">
					<Icon icon={ updateIcon } />
					{ updateAvailableLabel }
				</small>
				{ 1 === updatesCount ? (
					<Single extension={ extensionsWithUpdate[ 0 ] } />
				) : (
					<Multiple extensions={ extensionsWithUpdate } />
				) }
			</div>
		</Col>
	);
};

export default UpdateNotification;
