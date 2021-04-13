/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { useState, useEffect } from '@wordpress/element';
import { Spinner } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Header from './header';
import Tabs from './tabs';
import UpdateNotification from './update-notification';
import QueryStringRouter, { Route } from '../shared/query-string-router';
import AllExtensions from './all-extensions';
import FilteredExtensions from './filtered-extensions';
import { __ } from '@wordpress/i18n';

const Main = () => {
	const [ extensions, setExtensions ] = useState( false );

	useEffect( () => {
		apiFetch( { path: '/sensei-internal/v1/sensei-plugins' } )
			.then( ( result ) => {
				setExtensions( result );
			} )
			.catch( () => setExtensions( [] ) );
	}, [] );

	if ( false === extensions ) {
		return (
			<div className="sensei-extensions__loader">
				<Spinner />
			</div>
		);
	}

	const freeExtensions = extensions.filter(
		( extension ) => extension.price === 0
	);
	// TODO: Specify which plugins are third party
	const thirdPartyExtensions = extensions.filter(
		( extension ) => extension.price === 0
	);
	const installedExtensions = extensions.filter(
		( extension ) => extension.is_installed
	);

	const tabs = [
		{
			id: 'all',
			label: __( 'All', 'sensei-lms' ),
			count: extensions.length,
		},
		{
			id: 'free',
			label: __( 'Free', 'sensei-lms' ),
			count: freeExtensions.length,
		},
		{
			id: 'third-party',
			label: __( 'Third party', 'sensei-lms' ),
			count: thirdPartyExtensions.length,
		},
		{
			id: 'installed',
			label: __( 'Installed', 'sensei-lms' ),
			count: installedExtensions.length,
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

					<Route route="all">
						<AllExtensions extensions={ extensions } />
					</Route>
					<Route route="free">
						<FilteredExtensions extensions={ freeExtensions } />
					</Route>
					<Route route="third-party">
						<FilteredExtensions
							extensions={ thirdPartyExtensions }
						/>
					</Route>
					<Route route="installed">
						<FilteredExtensions
							extensions={ installedExtensions }
						/>
					</Route>
				</QueryStringRouter>
			</div>
		</main>
	);
};

export default Main;
