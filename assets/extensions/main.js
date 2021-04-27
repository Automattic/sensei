/**
 * WordPress dependencies
 */
import { Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import Header from './header';
import Tabs from './tabs';
import UpdateNotification from './update-notification';
import QueryStringRouter, { Route } from '../shared/query-string-router';
import AllExtensions from './all-extensions';
import FilteredExtensions from './filtered-extensions';
import { EXTENSIONS_STORE } from './store';

const Main = () => {
	const extensions = useSelect( ( select ) =>
		select( EXTENSIONS_STORE ).getExtensions()
	);

	if ( extensions.length === 0 ) {
		return (
			<div className="sensei-extensions__loader">
				<Spinner />
			</div>
		);
	}

	const freeExtensions = extensions.filter(
		( extension ) => extension.price === '0'
	);
	// TODO: Specify which plugins are third party
	const thirdPartyExtensions = extensions.filter(
		( extension ) => extension.price === '0'
	);
	const installedExtensions = extensions.filter(
		( extension ) => extension.is_installed
	);

	const tabs = [
		{
			id: 'all',
			label: __( 'All', 'sensei-lms' ),
			count: extensions.length,
			content: <AllExtensions extensions={ extensions } />,
		},
		{
			id: 'free',
			label: __( 'Free', 'sensei-lms' ),
			count: freeExtensions.length,
			content: <FilteredExtensions extensions={ freeExtensions } />,
		},
		{
			id: 'third-party',
			label: __( 'Third Party', 'sensei-lms' ),
			count: thirdPartyExtensions.length,
			content: <FilteredExtensions extensions={ thirdPartyExtensions } />,
		},
		{
			id: 'installed',
			label: __( 'Installed', 'sensei-lms' ),
			count: installedExtensions.length,
			content: <FilteredExtensions extensions={ installedExtensions } />,
		},
	];

	return (
		<main className="sensei-extensions">
			<div className="sensei-extensions__grid">
				<QueryStringRouter paramName="tab" defaultRoute="all">
					<div className="sensei-extensions__section sensei-extensions__grid__col --col-12">
						<Header />
						<Tabs tabs={ tabs } />
					</div>

					<UpdateNotification extensions={ extensions } />
					{ tabs.map( ( tab ) => (
						<Route key={ tab.id } route={ tab.id }>
							{ tab.content }
						</Route>
					) ) }
				</QueryStringRouter>
			</div>
		</main>
	);
};

export default Main;
