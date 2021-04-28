/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ExtensionActions from '../extension-actions';
import { useDispatch, useSelect } from '@wordpress/data';
import { EXTENSIONS_STORE } from '../store';
import { UpdateIcon } from '../../icons';

/**
 * Multiple update notification.
 *
 * @param {Object}   props            Component props.
 * @param {Array}    props.extensions Extensions with update.
 * @param {Function} props.onUpdate   Callback to call when update is clicked.
 */
const Multiple = ( { extensions, onUpdate } ) => {
	const componentInProgress = useSelect( ( select ) =>
		select( EXTENSIONS_STORE ).getComponentInProgress()
	);
	const { updateExtensions } = useDispatch( EXTENSIONS_STORE );

	const action = {
		key: 'update-button',
		children: __( 'Update all', 'sensei-lms' ),
		disabled: componentInProgress !== '',
		onClick: () => {
			onUpdate();
			updateExtensions( extensions, 'multiple-extension-notification' );
		},
	};

	if ( componentInProgress === 'multiple-extension-notification' ) {
		action.children = (
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

	return (
		<>
			<ul className="sensei-extensions__update-notification__list">
				{ extensions.map( ( extension ) => (
					<li
						key={ extension.product_slug }
						className="sensei-extensions__update-notification__list__item"
					>
						{ extension.title }{ ' ' }
						<a
							href={ extension.link }
							className="sensei-extensions__update-notification__version-link"
							target="_blank"
							rel="noreferrer external"
						>
							{ sprintf(
								// translators: placeholder is the version number.
								__( 'version %s', 'sensei-lms' ),
								extension.version
							) }
						</a>
					</li>
				) ) }
			</ul>

			<ExtensionActions actions={ [ action ] } />
		</>
	);
};

export default Multiple;
