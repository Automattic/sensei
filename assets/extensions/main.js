/**
 * WordPress dependencies
 */
import { Notice, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { EditorNotices } from '@wordpress/editor';

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
import { Grid, Col } from './grid';

const Main = () => {
	const { extensions } = useSelect( ( select ) => ( {
		extensions: select( EXTENSIONS_STORE ).getExtensions(),
	} ) );
	const { layout } = useSelect( ( select ) => ( {
		layout: select( EXTENSIONS_STORE ).getLayout(),
	} ) );
	const error = useSelect( ( select ) =>
		select( EXTENSIONS_STORE ).getError()
	);

	if ( extensions.length === 0 || layout.length === 0 ) {
		return (
			<div className="sensei-extensions__loader">
				<Spinner />
			</div>
		);
	}

	const freeExtensions = extensions.filter(
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
			content: (
				<AllExtensions extensions={ extensions } layout={ layout } />
			),
		},
		{
			id: 'free',
			label: __( 'Free', 'sensei-lms' ),
			count: freeExtensions.length,
			content: <FilteredExtensions extensions={ freeExtensions } />,
		},
		{
			id: 'installed',
			label: __( 'Installed', 'sensei-lms' ),
			count: installedExtensions.length,
			content: <FilteredExtensions extensions={ installedExtensions } />,
		},
	];

	return (
		<>
			<Grid as="main" className="sensei-extensions">
				<QueryStringRouter paramName="tab" defaultRoute="all">
					<Col className="sensei-extensions__section" cols={ 12 }>
						<Header />
						<Tabs tabs={ tabs } />
						{ error !== null && (
							<Notice status="error" isDismissible={ false }>
								{ error }
							</Notice>
						) }
					</Col>

					<UpdateNotification extensions={ extensions } />
					{ tabs.map( ( tab ) => (
						<Route key={ tab.id } route={ tab.id }>
							{ tab.content }
						</Route>
					) ) }
				</QueryStringRouter>
			</Grid>
			<EditorNotices />
		</>
	);
};

export default Main;
