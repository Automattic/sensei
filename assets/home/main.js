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
import TasksSection from './tasks';
import GetHelp from './sections/get-help';
import SenseiGuides from './sections/sensei-guides';
import LatestNews from './sections/latest-news';
import Extensions from './sections/extensions';

const Main = () => {
	useSenseiColorTheme();

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

				<Col as="section" className="sensei-home__section" cols={ 12 }>
					<TasksSection />
				</Col>

				<Col as="section" className="sensei-home__section" cols={ 6 }>
					<QuickLinks />
				</Col>

				<Col as="section" className="sensei-home__section" cols={ 6 }>
					<GetHelp />
				</Col>

				<SenseiProAd />

				<Col as="section" className="sensei-home__section" cols={ 6 }>
					<SenseiGuides />
				</Col>

				<Col as="section" className="sensei-home__section" cols={ 6 }>
					<LatestNews />
				</Col>

				<Col as="section" className="sensei-home__section" cols={ 12 }>
					<Extensions />
				</Col>
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
