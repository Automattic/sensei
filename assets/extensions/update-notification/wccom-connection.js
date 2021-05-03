/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Multiple from './multiple';
import UpdateAvailable from './update-available';
import { Col } from '../grid';
import { UpdateIcon } from '../../icons';
import ExtensionActions from '../extension-actions';

/**
 * WooCommerce.com connection component.
 *
 * @param {Object} props            Component props.
 * @param {Array}  props.extensions Extensions list.
 */
const WCCOMConnection = ( { extensions } ) => {
	const extensionsWithUpdate = extensions.filter(
		( extension ) => extension.has_update
	);

	const updatesCount = extensionsWithUpdate.length;

	if ( 0 === updatesCount ) {
		return null;
	}

	const actions = [
		{
			key: 'connect',
			children: __( 'Connect account', 'sensei-lms' ),
			href: window.sensei_extensions?.connectUrl,
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
					{ __(
						'Connect your site to your WooCommerce.com account to update.',
						'sensei-lms'
					) }
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

export default WCCOMConnection;
