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
import UpdateAvailableLabel from './update-available-label';
import { Col } from '../grid';
import updateIcon from '../../icons/update-icon';
import ExtensionActions from '../extension-actions';
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

	if ( 0 === updatesCount ) {
		return null;
	}

	const { updateExtensions } = useDispatch( EXTENSIONS_STORE );

	const inProgress = extensionsWithUpdate.some( ( extension ) =>
		isLoadingStatus( extension.status )
	);

	let actionProps = {
		key: 'update-button',
		onClick: () => {
			updateExtensions( extensions );
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
				<small className="sensei-extensions__update-badge">
					<Icon icon={ updateIcon } />
					<UpdateAvailableLabel updatesCount={ updatesCount } />
				</small>
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
