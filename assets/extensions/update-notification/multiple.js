/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ExtensionActions from '../extension-actions';
import { useDispatch } from '@wordpress/data';
import { EXTENSIONS_STORE, isLoadingStatus } from '../store';
import updateIcon from '../../icons/update-icon';

/**
 * Multiple update notification.
 *
 * @param {Object} props            Component props.
 * @param {Array}  props.extensions Extensions with update.
 */
const Multiple = ( { extensions } ) => {
	const { updateExtensions } = useDispatch( EXTENSIONS_STORE );

	const inProgress = extensions.some( ( extension ) =>
		isLoadingStatus( extension.status )
	);

	let actionProps = {
		key: 'update-button',
		onClick: () => {
			updateExtensions(
				extensions.map( ( extension ) => extension.product_slug )
			);
		},
	};

	if ( inProgress ) {
		actionProps = {
			children: __( 'Updating…', 'sensei-lms' ),
			className: 'sensei-extensions__rotating-icon',
			icon: updateIcon,
			disabled: true,
			...actionProps,
		};
	} else {
		actionProps = {
			children: __( 'Update all', 'sensei-lms' ),
			...actionProps,
		};
	}

	const actions = [ actionProps ];

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

			<ExtensionActions actions={ actions } />
		</>
	);
};

export default Multiple;
