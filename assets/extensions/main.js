/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { useState, useEffect } from '@wordpress/element';
import { Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Header from './header';
import Tabs from './tabs';
import UpdateNotification from './update-notification';
import QueryStringRouter, { Route } from '../shared/query-string-router';
import AllExtensions from './all-extensions';
import FilteredExtensions from './filtered-extensions';
import { Grid, Col } from './grid';

const Main = () => {
	const [ extensions, setExtensions ] = useState( false );

	useEffect( () => {
		apiFetch( {
			path: '/sensei-internal/v1/sensei-extensions?type=plugin',
		} )
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
		<Grid as="main" className="sensei-extensions">
			<QueryStringRouter paramName="tab" defaultRoute="all">
				<Col className="sensei-extensions__section" cols={ 12 }>
					<Header />
					<Tabs tabs={ tabs } />
				</Col>

				<UpdateNotification extensions={ extensions } />
				{ tabs.map( ( tab ) => (
					<Route key={ tab.id } route={ tab.id }>
						{ tab.content }
					</Route>
				) ) }
			</QueryStringRouter>
		</Grid>
	);
};

export default Main;
