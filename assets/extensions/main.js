/**
 * Internal dependencies
 */
import Header from './header';
import Tabs from './tabs';
import UpdateNotification from './update-notification';
import QueryStringRouter, { Route } from '../shared/query-string-router';
import AllExtensions from './all-extensions';
import FilteredExtensions from './filtered-extensions';

const Main = () => (
	<main className="sensei-extensions">
		<div className="sensei-extensions__grid">
			<QueryStringRouter paramName="tab" defaultRoute="all">
				<div className="sensei-extensions__section sensei-extensions__grid__col --col-12">
					<Header />
					<Tabs />
				</div>

				<section className="sensei-extensions__section sensei-extensions__grid__col --col-12">
					<UpdateNotification />
				</section>

				<Route route="all">
					<AllExtensions />
				</Route>
				<Route route="free">
					<FilteredExtensions />
				</Route>
				<Route route="third-party">
					<FilteredExtensions />
				</Route>
				<Route route="installed">
					<FilteredExtensions />
				</Route>
			</QueryStringRouter>
		</div>
	</main>
);

export default Main;
