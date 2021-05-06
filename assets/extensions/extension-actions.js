/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { checked } from '../icons/wordpress-icons';
import { EXTENSIONS_STORE, isLoadingStatus } from './store';
import updateIcon from '../icons/update-icon';

/**
 * Extension actions component.
 *
 * @param {Object} props         Component props.
 * @param {Array}  props.actions Actions array containing objects with props for link or button.
 */
const ExtensionActions = ( { actions } ) => (
	<ul className="sensei-extensions__extension-actions">
		{ actions.map( ( { key, children, ...actionProps } ) => (
			<li
				key={ key }
				className="sensei-extensions__extension-actions__item"
			>
				<Button
					isPrimary={ ! actionProps.href }
					isLink={ !! actionProps.href }
					{ ...actionProps }
				>
					{ children }
				</Button>
			</li>
		) ) }
	</ul>
);

export default ExtensionActions;

/**
 * Extension actions hook.
 *
 * @param {Object} extension Extension object.
 *
 * @return {Array|null} Array of actions, or null if it's not a valid extension.
 */
export const useExtensionActions = ( extension ) => {
	const { updateExtensions } = useDispatch( EXTENSIONS_STORE );

	if ( ! extension.product_slug ) {
		return null;
	}

	let actionProps = { key: 'main-button' };

	if ( isLoadingStatus( extension.status ) ) {
		actionProps = {
			children: __( 'Updating…', 'sensei-lms' ),
			className: 'sensei-extensions__rotating-icon',
			icon: updateIcon,
			disabled: true,
			...actionProps,
		};
	} else if ( extension.has_update ) {
		actionProps = {
			children: __( 'Update', 'sensei-lms' ),
			onClick: () =>
				updateExtensions( [ extension ], extension.product_slug ),
			disabled: ! extension.can_update,
			...actionProps,
		};
	} else if ( extension.is_installed ) {
		actionProps = {
			children: __( 'Installed', 'sensei-lms' ),
			icon: checked,
			disabled: true,
			...actionProps,
		};
	} else {
		const price =
			extension.price !== '0'
				? extension.price
				: __( 'Free', 'sensei-lms' );

		actionProps = {
			children: `${ __( 'Install', 'sensei-lms' ) } - ${ price }`,
			...actionProps,
		};
	}

	let buttons = [ actionProps ];

	if ( extension.link ) {
		buttons = [
			...buttons,
			{
				key: 'more-details',
				href: extension.link,
				className: 'sensei-extensions__extension-actions__details-link',
				target: '_blank',
				rel: 'noreferrer external',
				children: __( 'More details', 'sensei-lms' ),
			},
		];
	}

	return buttons;
};
