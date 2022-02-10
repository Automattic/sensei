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
import FeaturedProduct from './featured-product';
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

	const {
		extensions,
		connected,
		layout,
		isExtensionsLoading,
		error,
	} = useSelect( ( select ) => {
		const store = select( EXTENSIONS_STORE );

		return {
			isExtensionsLoading: ! store.hasFinishedResolution(
				'getExtensions'
			),
			extensions: store.getExtensions(),
			connected: store.getConnectionStatus(),
			layout: store.getLayout(),
			error: store.getError(),
		};
	} );

	if ( isExtensionsLoading ) {
		return (
			<div className="sensei-extensions__loader">
				<Spinner />
			</div>
		);
	}

	if ( 0 === extensions.length || 0 === layout.length ) {
		return <div>{ __( 'No extensions found.', 'sensei-lms' ) }</div>;
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
						{ /* TODO: Get the featured product data from the API */ }
						<FeaturedProduct
							title="Sensei Pro"
							excerpt="Everything you need to create and sell online courses"
							description={ `
								<p>By upgrading to Sensei Pro, you get all the great<br/>features found in Sensei LMS plus:</p>
								<ul>
									<li>WooCommerce integration</li>
									<li>Schedule ‘drip’ content</li>
									<li>Set expiration date of courses</li>
									<li>Advanced quiz features</li>
									<li>Interactive learning blocks (coming soon)</li>
									<li>Premium support</li>
								</ul>
							` }
							image="https://senseilms.com/wp-content/uploads/2021/05/sensei-content-drip__cover.png?w=568&h=522&crop=1"
							badgeLabel="new"
							price="$149.00"
							buttonLink="https://senseilms.com/checkout?add-to-cart="
						/>
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
