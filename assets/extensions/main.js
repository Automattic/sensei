/**
 * WordPress dependencies
 */
import { Notice, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { EditorNotices } from '@wordpress/editor';
import { RawHTML } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useSenseiColorTheme } from '../react-hooks/use-sensei-color-theme';
import Header from './header';
import Tabs from './tabs';
import UpdateNotification from './update-notification';
import WooCommerceNotice from './update-notification/woocommerce-notice';
import QueryStringRouter, { Route } from '../shared/query-string-router';
import AllExtensions from './all-extensions';
import FilteredExtensions from './filtered-extensions';
import { EXTENSIONS_STORE } from './store';
import { Grid, Col } from './grid';

const Main = () => {
	useSenseiColorTheme();

	const { extensions, connected, layout, error } = useSelect( ( select ) => {
		const store = select( EXTENSIONS_STORE );

		return {
			extensions: store.getExtensions(),
			connected: store.getConnectionStatus(),
			layout: store.getLayout(),
			error: store.getError(),
		};
	} );

	if ( 0 === extensions.length || 0 === layout.length ) {
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
	const wooExtensions = extensions.filter(
		( extension ) => extension.wccom_product_id
	);
	const nonWooExtensions = extensions.filter(
		( extension ) => ! extension.wccom_product_id
	);

	const tabs = [
		{
			id: 'all',
			label: __( 'All', 'sensei-lms' ),
			count: extensions.length,
			content: <AllExtensions layout={ layout } />,
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
								<RawHTML>{ error }</RawHTML>
							</Notice>
						) }
					</Col>

					<WooCommerceNotice
						connected={ connected }
						extensions={ wooExtensions }
					/>

					<UpdateNotification
						extensions={ connected ? extensions : nonWooExtensions }
					/>

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
