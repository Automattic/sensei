/**
 * Internal dependencies
 */
import Welcome from './welcome';
import Purpose from './purpose';
import Theme from './theme';
import UsageTracking from './usage-tracking';
import Newsletter from './newsletter';
import Features from './features';

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
		key: 'theme',
		container: <Theme />,
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
		key: 'features',
		container: <Features />,
	},
];

export default steps;
