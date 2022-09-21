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
				<Col as="section" className="sensei-home__section" cols={ 12 }>
					<Header />
				</Col>

				<Col cols={ 12 } as="section">
					<TaskList />
				</Col>

				<Col cols={ 6 } as="section">
					<QuickLinks />
				</Col>

				<Col cols={ 6 } as="section">
					<GetHelp />
				</Col>

				<Col cols={ 12 } as="section">
					<FeaturedProductSenseiPro />
				</Col>

				<Col cols={ 6 } as="section">
					<SenseiGuides />
				</Col>

				<Col cols={ 6 } as="section">
					<LatestNews />
				</Col>

				<Col cols={ 12 } as="section">
					<Extensions />
				</Col>
			</Grid>
			<EditorNotices />
		</>
	);
};

export default Main;
