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
import { UpdateIcon } from '../../icons';

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

	const children = inProgress ? (
		<>
			<UpdateIcon
				width="20"
				height="20"
				className="sensei-extensions__rotating-icon sensei-extensions__extension-actions__button-icon"
			/>
			{ __( 'Updating…', 'sensei-lms' ) }
		</>
	) : (
		__( 'Update all', 'sensei-lms' )
	);

	const actions = [
		{
			key: 'update-button',
			children,
			disabled: inProgress,
			onClick: () => {
				updateExtensions( extensions );
			},
		},
	];

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
