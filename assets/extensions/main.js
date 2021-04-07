/**
 * Internal dependencies
 */
import Header from './header';
import Tabs from './tabs';
import UpdateNotification from './update-notification';
import Extensions from './extensions';

const Main = () => (
	<main className="sensei-extensions">
		<Header />
		<Tabs />
		<UpdateNotification />
		<Extensions />
	</main>
);

export default Main;
