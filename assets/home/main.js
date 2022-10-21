/**
 * WordPress dependencies
 */
import { EditorNotices } from '@wordpress/editor';
import { applyFilters, addFilter } from '@wordpress/hooks';

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
import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import '../shared/data/api-fetch-preloaded-once';
import { Notice, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const Main = () => {
	useSenseiColorTheme();
	const [ data, setData ] = useState();
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
				<TasksSection data={ data.tasks } />

				<Col as="section" className="sensei-home__section" cols={ 6 }>
					<QuickLinks quickLinks={ data?.quick_links } />
				</Col>

				<Col as="section" className="sensei-home__section" cols={ 6 }>
					<GetHelp categories={ data?.help } />
				</Col>

				<SenseiProAd show={ data?.promo_banner.is_visible } />

				<Col as="section" className="sensei-home__section" cols={ 6 }>
					<SenseiGuides data={ data?.guides } />
				</Col>

				<Col as="section" className="sensei-home__section" cols={ 6 }>
					<LatestNews data={ data?.news } />
				</Col>

				<Col
					as="section"
					className="sensei-home__section sensei-home__section__extensions"
					cols={ 12 }
				>
					<Extensions />
				</Col>
			</>
		);
	}

	/**
	 * Filters the component that will be injected on the top of the Sensei Home
	 *
	 * @since $$next-version$$
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

				{ topRow }

				{ content }
			</Grid>
		</>
	);
};

/**
 * Filter to add the notices section based on the EditorNotices component.
 *
 * @param {JSX.Element} previous The previous element to be added
 * @return {JSX.Element} The new top of the Sensei Home page, with the editor notices as a column.
 */
function addNotices( previous ) {
	return (
		<>
			{ previous }
			<Col as="section" className="sensei-home__section" cols={ 12 }>
				<EditorNotices />
			</Col>
		</>
	);
}

addFilter( 'sensei.home.top', 'sensei/home/top/add-notices', addNotices );

export default Main;
