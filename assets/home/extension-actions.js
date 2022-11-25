/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import { Button } from '@wordpress/components';
import { check } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { EXTENSIONS_STORE, isLoadingStatus } from '../extensions/store';
import UpdateIcon from '../icons/update.svg';

/**
 * Extension actions component.
 *
 * @param {Object} props         Component props.
 * @param {Array}  props.actions Actions array containing objects with props for link or button.
 */
const ExtensionActions = ( { actions } ) => (
	<ul className="sensei-home__card__extension-actions">
		{ actions.map( ( { key, children, ...actionProps } ) => (
			<li
				key={ key }
				className="sensei-home__card__extension-actions__item"
			>
				<Button
					isPrimary={ ! actionProps.href }
					isSecondary={ !! actionProps.href }
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
	const { installExtension, updateExtensions } = useDispatch(
		EXTENSIONS_STORE
	);

	if ( ! extension.product_slug ) {
		return null;
	}

	let actionProps = { key: 'main-button' };

	if ( isLoadingStatus( extension.status ) ) {
		actionProps = {
			children: __( 'In progress…', 'sensei-lms' ),
			className: 'sensei-home__rotating-icon',
			icon: UpdateIcon,
			disabled: true,
			...actionProps,
		};
	} else if ( extension.has_update ) {
		actionProps = {
			children: __( 'Update', 'sensei-lms' ),
			onClick: () => updateExtensions( [ extension.product_slug ] ),
			disabled: ! extension.can_update,
			...actionProps,
		};
	} else if ( extension.is_installed ) {
		actionProps = {
			children: __( 'Installed', 'sensei-lms' ),
			icon: check,
			disabled: true,
			...actionProps,
		};
	} else {
		const price =
			extension.price !== '0' && extension.price !== 0
				? extension.price
				: __( 'Free', 'sensei-lms' );

		actionProps = {
			children: `${ __( 'Install', 'sensei-lms' ) } - ${ price }`,
			onClick: () => {
				installExtension( extension.product_slug );
			},
			...actionProps,
		};
	}

	let buttons = [ actionProps ];

	const href =
		extension.is_installed && extension.has_update
			? extension.changelog_url
			: extension.link;

	if ( href ) {
		buttons = [
			...buttons,
			{
				key: 'more-details',
				href,
				target: '_blank',
				rel: 'noreferrer external',
				children: __( 'More details', 'sensei-lms' ),
			},
		];
	}

	return buttons;
};
