/**
 * Internal dependencies
 */
import Welcome from './welcome';
import Purpose from './purpose';
import UsageTracking from './usage-tracking';
import Newsletter from './newsletter';
import Preparing from './preparing';

const steps = [
	{
		key: 'welcome',
		container: <Welcome />,
	},
	{
		key: 'purpose',
		container: <Purpose />,
	},
	{
		key: 'tracking',
		container: <UsageTracking />,
	},
	{
		key: 'newsletter',
		container: <Newsletter />,
	},
	{
		key: 'preparing',
		container: <Preparing />,
	},
];

export default steps;
