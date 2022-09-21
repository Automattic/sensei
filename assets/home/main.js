/**
 * WordPress dependencies
 */
import { EditorNotices } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import { useSenseiColorTheme } from '../react-hooks/use-sensei-color-theme';
import FeaturedProductSenseiPro from './featured-product-sensei-pro';
import Header from './header';
import { Col, Grid } from './grid';
import QuickLinks from './sections/quick-links';
import TaskList from './sections/task-list';
import GetHelp from './sections/get-help';
import SenseiGuides from './sections/sensei-guides';
import LatestNews from './sections/latest-news';
import Extensions from './sections/extensions';

const Main = () => {
	useSenseiColorTheme();

	return (
		<>
			<Grid as="main" className="sensei-home">
				<Col className="sensei-home__section" cols={ 12 }>
					<Header />
				</Col>

				<Col cols={ 12 }>
					<TaskList />
				</Col>

				<Col cols={ 6 }>
					<QuickLinks />
				</Col>

				<Col cols={ 6 }>
					<GetHelp />
				</Col>

				<Col cols={ 12 }>
					<FeaturedProductSenseiPro />
				</Col>

				<Col cols={ 6 }>
					<SenseiGuides />
				</Col>

				<Col cols={ 6 }>
					<LatestNews />
				</Col>

				<Col cols={ 12 }>
					<Extensions />
				</Col>
			</Grid>
			<EditorNotices />
		</>
	);
};

export default Main;
