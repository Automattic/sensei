import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { getWoocommerceComPurchaseUrl } from '../../setup-wizard/helpers/woocommerce-com';
import { useState, createPortal } from '@wordpress/element';

/**
 * Add extension installation method and status to a component.
 *
 * @param {Function} Component Wrapped component.
 */
export function withInstaller( Component ) {
	return ( props ) => {
		const { slug, source } = props;
		const [ status, setStatus ] = useState( {} );

		/**
		 * Start Woocommerce.com purchase & installation flow by opening a cart checkout page.
		 */
		function installWccom() {
			const wcPurchaseUrl = getWoocommerceComPurchaseUrl(
				[ slug ],
				window.sensei_extensions_data.wccom
			);
			window.open( wcPurchaseUrl );
		}

		/**
		 * Install extension from Wordpress.org.
		 */
		async function installWporg() {
			const path = '/sensei-internal/v1/extensions/extensions-install';
			setStatus( { status: 'installing' } );
			const postResult = await apiFetch( {
				path,
				method: 'POST',
				data: { slug },
			} );
			setStatus( postResult );
			const pollInterval =
				'installing' === postResult.status && setInterval( poll, 2000 );

			async function poll() {
				const pollResult = await apiFetch( {
					path: addQueryArgs( path, { slug } ),
					method: 'GET',
				} );
				setStatus( pollResult );
				if ( 'installing' !== pollResult.status ) {
					clearInterval( pollInterval );
				}
			}
		}

		const install = 'wporg' === source ? installWporg : installWccom;

		return <Component { ...props } { ...status } { ...{ install } } />;
	};
}

/**
 * Render an extension install button depending on installation status.
 *
 * @param {Object}   props
 * @param {string}   props.status         Extension installation status.
 * @param {string}   props.error          Error message.
 * @param {Function} props.install        Start installation.
 * @param {Element}  props.errorContainer Where to render the error message.
 */
export function SenseiExtensionInstall( {
	status,
	error,
	install,
	errorContainer,
} ) {
	const Button = ( props ) => (
		<button className="button-primary" { ...props }>
			{ props.children }
		</button>
	);
	switch ( status ) {
		case 'installing':
			return (
				<>
					<Button disabled>
						{ __( 'Installing..', 'sensei-lms' ) }
					</Button>
					<span className="spinner is-active" />
				</>
			);
		case 'installed':
			return (
				<Button disabled>{ __( 'Installed', 'sensei-lms' ) }</Button>
			);
		default:
			return (
				<>
					<Button onClick={ install }>
						{ __( 'Install', 'sensei-lms' ) }
					</Button>
					{ error &&
						createPortal(
							<span className="error">{ error }</span>,
							errorContainer
						) }
				</>
			);
	}
}
