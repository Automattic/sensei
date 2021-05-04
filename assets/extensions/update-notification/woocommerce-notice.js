/**
 * WordPress dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Multiple from './multiple';
import UpdateAvailable from './update-available';
import { Col } from '../grid';
import ExtensionActions from '../extension-actions';

/**
 * WooCommerce notice component.
 *
 * @param {Object} props             Component props.
 * @param {Array}  props.extensions  Extensions list.
 * @param {Array}  props.isConnected Whether the site is connected to WC.com.
 */
const WooCommerceNotice = ( { extensions, isConnected } ) => {
	const extensionsWithUpdate = extensions.filter(
		( extension ) => extension.has_update
	);

	const updatesCount = extensionsWithUpdate.length;
	const isInstalled = ! window.sensei_extensions?.installUrl;
	const isActive = ! window.sensei_extensions?.activateUrl;

	if ( 0 === updatesCount || ( isInstalled && isActive && isConnected ) ) {
		return null;
	}

	const showConnectionNotice = ! isConnected && isInstalled && isActive;
	const title =
		( showConnectionNotice &&
			_n(
				'Your site needs to be connected to your WooCommerce.com account before this extension can be updated.',
				'Your site needs to be connected to your WooCommerce.com account before these extensions can be updated.',
				updatesCount,
				'sensei-lms'
			) ) ||
		( ! isInstalled &&
			_n(
				'WooCommerce needs to be installed before this extension can be updated.',
				'WooCommerce needs to be installed before these extensions can be updated.',
				updatesCount,
				'sensei-lms'
			) ) ||
		( ! isActive &&
			_n(
				'WooCommerce needs to be activated before this extension can be updated.',
				'WooCommerce needs to be activated before these extensions can be updated.',
				updatesCount,
				'sensei-lms'
			) );

	const actions = [
		{
			key:
				( showConnectionNotice && 'connect' ) ||
				( ! isInstalled && 'install' ) ||
				( ! isActive && 'activate' ),
			children:
				( showConnectionNotice &&
					__( 'Connect account', 'sensei-lms' ) ) ||
				( ! isInstalled &&
					__( 'Install WooCommerce', 'sensei-lms' ) ) ||
				( ! isActive && __( 'Activate WooCommerce', 'sensei-lms' ) ),
			href:
				( showConnectionNotice &&
					window.sensei_extensions?.connectUrl ) ||
				window.sensei_extensions?.installUrl ||
				window.sensei_extensions?.activateUrl,
		},
	];

	return (
		<Col as="section" className="sensei-extensions__section" cols={ 12 }>
			<div
				role="alert"
				className="sensei-extensions__update-notification"
			>
				<UpdateAvailable updatesCount={ updatesCount } />

				<h3 className="sensei-extensions__update-notification__title">
					{ title }
				</h3>

				{ 1 === updatesCount ? (
					<>
						<div className="sensei-extensions__update-notification__description">
							<span>{ extensionsWithUpdate[ 0 ].title } </span>
							<a
								href={ extensionsWithUpdate[ 0 ].link }
								className="sensei-extensions__update-notification__version-link"
								target="_blank"
								rel="noreferrer external"
							>
								{ sprintf(
									// translators: placeholder is the version number.
									__( 'version %s', 'sensei-lms' ),
									extensionsWithUpdate[ 0 ].version
								) }
							</a>
						</div>
						<ExtensionActions actions={ actions } />
					</>
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

export default WooCommerceNotice;
