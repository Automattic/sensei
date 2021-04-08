/**
 * WordPress dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { Icon, update } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import ExtensionActions from './extension-actions';

/**
 * Update notification component.
 *
 * @param {Object} props            Component props.
 * @param {Array}  props.extensions Extensions list.
 */
const UpdateNotification = ( { extensions } ) => {
	const extensionsWithUpdate = extensions.filter(
		( extension ) => extension.has_update
	);

	const updatesCount = extensionsWithUpdate.length;

	if ( 0 === updatesCount ) {
		return null;
	}

	const extensionActionsProps =
		updatesCount === 1
			? {
					detailsLink: extensionsWithUpdate[ 0 ].link,
					buttonLabel: __( 'Update', 'sensei-lms' ),
			  }
			: {
					buttonLabel: __( 'Update all', 'sensei-lms' ),
			  };

	return (
		<section className="sensei-extensions__section --col-12">
			<div
				role="alert"
				className="sensei-extensions__update-notification"
			>
				<small className="sensei-extensions__update-badge">
					<Icon icon={ update } />
					{ updatesCount === 1
						? __( 'Update available', 'sensei-lms' )
						: sprintf(
								// translators: placeholder is number of updates available.
								_n(
									'%d update available',
									'%d updates available',
									updatesCount,
									'sensei-lms'
								),
								updatesCount
						  ) }
				</small>
				{ updatesCount === 1 ? (
					<>
						<h3 className="sensei-extensions__update-notification__title">
							{ extensionsWithUpdate[ 0 ].title }
						</h3>
						<p className="sensei-extensions__update-notification__description">
							{ extensionsWithUpdate[ 0 ].excerpt }
						</p>
					</>
				) : (
					<ul className="sensei-extensions__update-notification__list">
						{ extensionsWithUpdate.map( ( extension ) => (
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
				) }
				<ExtensionActions { ...extensionActionsProps } />
			</div>
		</section>
	);
};

export default UpdateNotification;
