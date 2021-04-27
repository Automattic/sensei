/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { checked } from '../icons/wordpress-icons';
import { useDispatch, useSelect } from '@wordpress/data';
import { EXTENSIONS_STORE } from './store';

/**
 * Extension actions component.
 *
 * @param {Object} props           Component props.
 * @param {string} props.extension Extensions related to the component.
 */
const SingleExtensionActions = ( { extension } ) => {
	let disabledButton = useSelect(
		( select ) => select( EXTENSIONS_STORE ).getButtonsDisabled(),
		[]
	);
	const { updateExtensions } = useDispatch( EXTENSIONS_STORE );

	let buttonLabel = '';
	let buttonAction = () => {};

	if ( extension.canUpdate ) {
		buttonLabel = __( 'Update', 'sensei-lms' );
		buttonAction = () => updateExtensions( [ extension ] );
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
		buttonLabel = `${ __( 'Install', 'sensei-lms' ) } - ${
			extension.price !== '0'
				? extension.price
				: __( 'Free', 'sensei-lms' )
		}`;
	}

	return (
		<ul className="sensei-extensions__extension-actions">
			<li className="sensei-extensions__extension-actions__item">
				<button
					className="button button-primary"
					disabled={ disabledButton }
					onClick={ buttonAction }
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
