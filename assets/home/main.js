/**
 * WordPress dependencies
 */
import { applyFilters } from '@wordpress/hooks';
import { Spinner, Notice } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useSenseiColorTheme } from '../react-hooks/use-sensei-color-theme';
import SenseiProAd from './sections/sensei-pro-ad';
import Header from './header';
import { Col, Grid } from './grid';
import QuickLinks from './sections/quick-links';
import TasksSection from './tasks-section';
import GetHelp from './sections/get-help';
import SenseiGuides from './sections/sensei-guides';
import LatestNews from './sections/latest-news';
import Extensions from './sections/extensions';
import '../shared/data/api-fetch-preloaded-once';
import Notices from './notices';

const Main = () => {
	useSenseiColorTheme();
	const [ data, setData ] = useState( {} );
	const [ error, setError ] = useState( null );
	const [ isFetching, setIsFetching ] = useState( true );

	useEffect( () => {
		async function fetchAndSetData() {
			try {
				const remoteData = await apiFetch( {
					path: '/sensei-internal/v1/home',
					method: 'GET',
				} );
				setData( remoteData );
				setIsFetching( false );
			} catch ( exceptionError ) {
				setError( exceptionError );
				setIsFetching( false );
			}
		}
		fetchAndSetData();
	}, [] );

	let content = null;
	const notices = data?.notices ?? {};

	if ( isFetching ) {
		content = <Spinner />;
	} else if ( error ) {
		content = (
			<Col as="section" className="sensei-home__section" cols={ 12 }>
				<Notice status="error" isDismissible={ false }>
					{ __(
						'An error has occurred while fetching the data. Please try again later!',
						'sensei-lms'
					) }
					<br />
					{ __( 'Error details:', 'sensei-lms' ) } { error.message }
				</Notice>
			</Col>
		);
	} else {
		content = (
			<>
				{ data.tasks && <TasksSection data={ data.tasks } /> }

				{ data.quick_links && data.quick_links.length > 0 && (
					<Col
						as="section"
						className="sensei-home__section"
						cols={ 6 }
					>
						<QuickLinks quickLinks={ data.quick_links } />
					</Col>
				) }

				{ data.help && data.help.length > 0 && (
					<Col
						as="section"
						className="sensei-home__section"
						cols={ 6 }
					>
						<GetHelp categories={ data.help } />
					</Col>
				) }

				{ data.promo_banner && (
					<SenseiProAd show={ data.promo_banner.is_visible } />
				) }

				{ data.guides && data.guides?.items.length > 0 && (
					<Col
						as="section"
						className="sensei-home__section"
						cols={ 6 }
					>
						<SenseiGuides data={ data.guides } />
					</Col>
				) }

				{ data.news && data.news?.items.length > 0 && (
					<Col
						as="section"
						className="sensei-home__section"
						cols={ 6 }
					>
						<LatestNews data={ data.news } />
					</Col>
				) }

				{ data.show_extensions && (
					<Col
						as="section"
						className="sensei-home__section sensei-home__section__extensions"
						cols={ 12 }
					>
						<Extensions />
					</Col>
				) }
			</>
		);
	}

	const { dismissNoticesNonce } = window.sensei_home;

	/**
	 * Filters the component that will be injected on the top of the Sensei Home
	 *
	 * @since 4.8.0
	 * @param {JSX.Element} element The element to be injected
	 * @return {JSX.Element} Filtered element.
	 */
	const topRow = applyFilters( 'sensei.home.top', null );

	return (
		<>
			<Grid as="main" className="sensei-home">
				<Col as="section" className="sensei-home__section" cols={ 12 }>
					<Header />
				</Col>

				{ Object.keys( notices ).length > 0 ? (
					<Col
						as="section"
						className="sensei-home__section sensei-home__notices"
						cols={ 12 }
					>
						<Notices
							notices={ notices }
							dismissNonce={ dismissNoticesNonce }
						/>
					</Col>
				) : null }

				{ topRow }

				{ content }
			</Grid>
		</>
	);
};

export default Main;
