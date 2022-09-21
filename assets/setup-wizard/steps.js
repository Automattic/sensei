/**
 * Internal dependencies
 */
import Welcome from './welcome';
import Purpose from './purpose';
import UsageTracking from './usage-tracking';
import Features from './features';
import Ready from './ready';

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
		key: 'features',
		container: <Features />,
	},
	{
		key: 'ready',
		container: <Ready />,
	},
];

export default steps;
