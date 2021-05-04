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
import { UpdateIcon } from '../icons';

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

	const mainButtonProps = {};

	if ( isLoadingStatus( extension.status ) ) {
		mainButtonProps.children = (
			<>
				<UpdateIcon
					width="20"
					height="20"
					className="sensei-extensions__rotating-icon sensei-extensions__extension-actions__button-icon"
				/>
				{ __( 'Updating…', 'sensei-lms' ) }
			</>
		);
	} else if ( extension.canUpdate ) {
		mainButtonProps.children = __( 'Update', 'sensei-lms' );
		mainButtonProps.onClick = () =>
			updateExtensions( [ extension ], extension.product_slug );
	} else if ( extension.is_installed ) {
		mainButtonProps.icon = checked;
		mainButtonProps.children = __( 'Installed', 'sensei-lms' );
	} else {
		mainButtonProps.children = `${ __( 'Install', 'sensei-lms' ) } - ${
			extension.price !== '0'
				? extension.price
				: __( 'Free', 'sensei-lms' )
		}`;
	}

	let buttons = [
		{
			key: 'main-button',
			disabled:
				isLoadingStatus( extension.status ) ||
				( extension.is_installed && ! extension.canUpdate ),
			...mainButtonProps,
		},
	];

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
