/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { checked } from '../icons/wordpress-icons';

const getButtonLabel = ( extensions ) => {
	if ( extensions.length === 1 ) {
		if ( extensions[ 0 ].canInstall ) {
			return __( 'Update', 'sensei-lms' );
		} else if ( extensions[ 0 ].is_installed ) {
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
			extensions[ 0 ].price !== '0'
				? extensions[ 0 ].price
				: __( 'Free', 'sensei-lms' )
		}`;
	}

	return __( 'Update all', 'sensei-lms' );
};

/**
 * Extension actions component.
 *
 * @param {Object}   props            Component props.
 * @param {string}   props.extensions Extensions related to the component.
 * @param {Function} props.onClick    Action button callback.
 */
const ExtensionActions = ( { extensions = [], onClick = () => {} } ) => {
	const disabledButton = false;

	const buttonLabel = getButtonLabel( extensions );

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
			{ extensions.length === 1 && (
				<li className="sensei-extensions__extension-actions__item">
					<a
						href={ extensions[ 0 ].link }
						className="sensei-extensions__extension-actions__details-link"
						target="_blank"
						rel="noreferrer external"
					>
						{ __( 'More details', 'sensei-lms' ) }
					</a>
				</li>
			) }
		</ul>
	);
};

export default ExtensionActions;
