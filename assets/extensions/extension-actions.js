/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon } from '@wordpress/icons';
import { RawHTML } from '@wordpress/element';

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
		} else if ( extension.is_installed ) {
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
			const buttonText = `${ __( 'Install', 'sensei-lms' ) } - ${
				extension.price !== 0
					? extension.price
					: __( 'Free', 'sensei-lms' )
			}`;
			buttonLabel = <RawHTML>{ buttonText }</RawHTML>;
		}
	}

	return (
		<ul className="sensei-extensions__extension-actions">
			<li className="sensei-extensions__extension-actions__item">
				<button
					className="button button-primary"
					disabled={ disabledButton }
				>
					{ buttonLabel }
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
