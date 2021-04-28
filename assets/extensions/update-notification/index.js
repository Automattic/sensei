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
import { Col } from '../grid';
import { UpdateIcon } from '../../icons';

/**
 * Update notification component.
 *
 * @param {Object} props            Component props.
 * @param {Array}  props.extensions Extensions list.
 */
const UpdateNotification = ( { extensions } ) => {
	const [ updateClicked, setUpdateClicked ] = useState( false );
	const extensionsWithUpdate = extensions.filter(
		( extension ) => extension.canUpdate
	);

	const updatesCount = extensionsWithUpdate.length;

	if ( 0 === updatesCount ) {
		if ( updateClicked ) {
			return (
				<Col
					as="section"
					className="sensei-extensions__section"
					cols={ 12 }
				>
					<div
						role="alert"
						className="sensei-extensions__update-notification"
					>
						<p className="sensei-extensions__update-message">
							{ __(
								'Update completed succesfully!',
								'sensei-lms'
							) }
						</p>
					</div>
				</Col>
			);
		}

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
					<UpdateIcon />
					{ updateAvailableLabel }
				</small>
				{ 1 === updatesCount ? (
					<Single
						extension={ extensionsWithUpdate[ 0 ] }
						onUpdate={ () => setUpdateClicked( true ) }
					/>
				) : (
					<Multiple
						extensions={ extensionsWithUpdate }
						onUpdate={ () => setUpdateClicked( true ) }
					/>
				) }
			</div>
		</Col>
	);
};

export default UpdateNotification;
