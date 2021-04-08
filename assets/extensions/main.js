/**
 * Internal dependencies
 */
import Header from './header';
import Tabs from './tabs';
import UpdateNotification from './update-notification';
import Extensions from './extensions';
import QueryStringRouter, { Route } from '../shared/query-string-router';

const Main = () => (
	<main className="sensei-extensions">
		<div className="sensei-extensions__sections">
			<QueryStringRouter paramName="tab" defaultRoute="all">
				<div className="sensei-extensions__section --col-12">
					<Header />
					<Tabs />
				</div>

				<section className="sensei-extensions__section --col-12">
					<UpdateNotification />
				</section>

				<Route route="all">
					<Extensions />
				</Route>
				<Route route="free">Free</Route>
				<Route route="third-party">Thid party</Route>
				<Route route="installed">Installed</Route>
			</QueryStringRouter>
		</div>
	</main>
);

export default Main;
