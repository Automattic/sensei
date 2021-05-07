/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Single from './single';
import Multiple from './multiple';
import UpdateAvailable from './update-available';
import { Col } from '../grid';
import updateIcon from '../../icons/update-icon';
import { useDispatch } from '@wordpress/data';
import { EXTENSIONS_STORE, isLoadingStatus } from '../store';

/**
 * Update notification component.
 *
 * @param {Object} props            Component props.
 * @param {Array}  props.extensions Extensions list.
 */
const UpdateNotification = ( { extensions } ) => {
	const extensionsWithUpdate = extensions.filter(
		( extension ) => extension.can_update && extension.has_update
	);

	const updatesCount = extensionsWithUpdate.length;
	const { updateExtensions } = useDispatch( EXTENSIONS_STORE );

	if ( 0 === updatesCount ) {
		return null;
	}

	const inProgress = extensionsWithUpdate.some( ( extension ) =>
		isLoadingStatus( extension.status )
	);

	let actionProps = {
		key: 'update-button',
		onClick: () => {
			updateExtensions(
				extensionsWithUpdate.map(
					( extension ) => extension.product_slug
				)
			);
		},
	};

	if ( inProgress ) {
		actionProps = {
			children: __( 'Updating…', 'sensei-lms' ),
			className: 'sensei-extensions__rotating-icon',
			icon: updateIcon,
			disabled: true,
			...actionProps,
		};
	} else {
		actionProps = {
			children: __( 'Update all', 'sensei-lms' ),
			...actionProps,
		};
	}

	const actions = [ actionProps ];

	return (
		<Col as="section" className="sensei-extensions__section" cols={ 12 }>
			<div
				role="alert"
				className="sensei-extensions__update-notification"
			>
				<UpdateAvailable updatesCount={ updatesCount } />
				{ 1 === updatesCount ? (
					<Single extension={ extensionsWithUpdate[ 0 ] } />
				) : (
					<Multiple
						extensions={ extensionsWithUpdate }
						actions={ actions }
					/>
				) }
			</div>
		</Col>
	);
};

export default UpdateNotification;
