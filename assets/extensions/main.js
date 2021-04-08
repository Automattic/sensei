/**
 * Internal dependencies
 */
import Header from './header';
import Tabs from './tabs';
import UpdateNotification from './update-notification';
import Extensions from './extensions';

const Main = () => (
	<main className="sensei-extensions">
		<div className="sensei-extensions__sections">
			<div className="sensei-extensions__section --col-12">
				<Header />
				<Tabs />
			</div>

			<section className="sensei-extensions__section --col-12">
				<UpdateNotification />
			</section>

			<Extensions />
		</div>
	</main>
);

export default Main;
