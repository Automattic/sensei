/**
 * WordPress dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Single from './single';
import Multiple from './multiple';
import { UpdateIcon } from '../../icons';

/**
 * Update notification component.
 *
 * @param {Object} props            Component props.
 * @param {Array}  props.extensions Extensions list.
 */
const UpdateNotification = ( { extensions } ) => {
	const [ updateStarted, setUpdateStarted ] = useState( false );
	const extensionsWithUpdate = extensions.filter(
		( extension ) => extension.canUpdate
	);

	const updatesCount = extensionsWithUpdate.length;

	if ( 0 === updatesCount && ! updateStarted ) {
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
		<section className="sensei-extensions__section sensei-extensions__grid__col --col-12">
			<div
				role="alert"
				className="sensei-extensions__update-notification"
			>
				<small className="sensei-extensions__update-badge">
					<UpdateIcon />
					{ updateAvailableLabel }
				</small>
				{ 1 === updatesCount ? (
					<Single extension={ extensionsWithUpdate[ 0 ] } />
				) : (
					<Multiple
						extensions={ extensionsWithUpdate }
						onClick={ () => setUpdateStarted( true ) }
					/>
				) }
			</div>
		</section>
	);
};

export default UpdateNotification;
