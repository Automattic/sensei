/**
 * Internal dependencies
 */
import ExtensionActions from '../extension-actions';
/**
 * WordPress dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { EXTENSIONS_STORE } from '../store';
import { UpdateIcon } from '../../icons';
import { __ } from '@wordpress/i18n';

/**
 * Single update notification.
 *
 * @param {Object}   props           Component props.
 * @param {Object}   props.extension Extension with update.
 * @param {Function} props.onUpdate  Callback to call when update is clicked.
 */
const Single = ( { extension, onUpdate } ) => {
	const componentInProgress = useSelect( ( select ) =>
		select( EXTENSIONS_STORE ).getComponentInProgress()
	);
	const { updateExtensions } = useDispatch( EXTENSIONS_STORE );

	let actions = [
		{
			key: 'update-button',
			children: __( 'Update', 'sensei-lms' ),
			disabled: componentInProgress !== '',
			onClick: () => {
				onUpdate();
				updateExtensions(
					[ extension ],
					'single-extension-notification'
				);
			},
		},
	];

	if ( componentInProgress === 'single-extension-notification' ) {
		actions[ 0 ].children = (
			<>
				<UpdateIcon
					width="20"
					height="20"
					className="sensei-extensions__rotating-icon sensei-extensions__extension-actions__button-icon"
				/>
				{ __( 'Updating…', 'sensei-lms' ) }
			</>
		);
	}

	if ( extension.link ) {
		actions = [
			...actions,
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

	return (
		<>
			<h3 className="sensei-extensions__update-notification__title">
				{ extension.title }
			</h3>
			<p className="sensei-extensions__update-notification__description">
				{ extension.excerpt }
			</p>
			<ExtensionActions actions={ actions } />
		</>
	);
};

export default Single;
