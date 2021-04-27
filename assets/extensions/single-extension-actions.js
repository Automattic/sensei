/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { checked } from '../icons/wordpress-icons';

const getButtonLabel = ( extension ) => {
	if ( extension.canInstall ) {
		return __( 'Update', 'sensei-lms' );
	} else if ( extension.is_installed ) {
		return (
			<>
				<Icon
					className="sensei-extensions__extension-actions__button-icon"
					icon={ checked }
					size={ 14 }
				/>{ ' ' }
				{ __( 'Installed', 'sensei-lms' ) }
			</>
		);
	}

	return `${ __( 'Install', 'sensei-lms' ) } - ${
		extension.price !== '0' ? extension.price : __( 'Free', 'sensei-lms' )
	}`;
};

/**
 * Extension actions component.
 *
 * @param {Object}   props           Component props.
 * @param {string}   props.extension Extensions related to the component.
 * @param {Function} props.onClick   Action button callback.
 */
const SingleExtensionActions = ( { extension, onClick = () => {} } ) => {
	const disabledButton = false;

	const buttonLabel = getButtonLabel( extension );

	return (
		<ul className="sensei-extensions__extension-actions">
			<li className="sensei-extensions__extension-actions__item">
				<button
					className="button button-primary"
					disabled={ disabledButton }
					onClick={ onClick }
				>
					{ buttonLabel }
				</button>
			</li>
			<li className="sensei-extensions__extension-actions__item">
				<a
					href={ extension.link }
					className="sensei-extensions__extension-actions__details-link"
					target="_blank"
					rel="noreferrer external"
				>
					{ __( 'More details', 'sensei-lms' ) }
				</a>
			</li>
		</ul>
	);
};

export default SingleExtensionActions;
