/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { checked } from '../icons/wordpress-icons';

/**
 * Extension actions component.
 *
 * @param {Object} props             Component props.
 * @param {string} props.extension   Extension object.
 * @param {string} props.buttonLabel Button label.
 */
const ExtensionActions = ( { extension = {}, buttonLabel } ) => {
	let disabledButton = false;

	if ( ! buttonLabel ) {
		if ( extension.has_update ) {
			buttonLabel = __( 'Update', 'sensei-lms' );
		} else if ( extension.version ) {
			buttonLabel = (
				<>
					<Icon
						className="sensei-extensions__extension-actions__button-icon"
						icon={ checked }
						size={ 14 }
					/>{ ' ' }
					{ __( 'Installed', 'sensei-lms' ) }
				</>
			);
			disabledButton = true;
		} else {
			buttonLabel = `${ __( 'Install', 'sensei-lms' ) } - ${
				extension.price
			}`;
		}
	}

	return (
		<ul className="sensei-extensions__extension-actions">
			<li className="sensei-extensions__extension-actions__item">
				<button
					className="button button-primary"
					disabled={ disabledButton }
				>
					{ buttonLabel || __( 'Install - $29.99', 'sensei-lms' ) }
				</button>
			</li>
			{ extension.link && (
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
			) }
		</ul>
	);
};

export default ExtensionActions;
