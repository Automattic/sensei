/**
 * External dependencies
 */
import { WpcomTourKit } from '@automattic/tour-kit';
import _ from 'lodash';

/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';
/**
 * Internal dependencies
 */
import { TourStep } from '../../types';

/**
 * Renders a tour kit component using Sensei.
 *
 * @param {Object}     props                  - Component props.
 * @param {TourStep[]} props.steps            - An array of steps to include in the tour.
 * @param {Object}     [props.extraConfig={}] - Additional configuration options for the tour kit.
 */
function SenseiTourKit( { steps, extraConfig = {} } ) {
	const [ showTour, setShowTour ] = useState( true );

	const config = {
		steps,
		closeHandler: () => setShowTour( false ),
		options: {
			effects: {
				spotlight: null,
				// spotlight: { // To enable spotlighting
				// 	interactivity: {
				// 		enabled: true, // Needed to allow the user to click inside the spotlighted element
				// 	},
				// },
				liveResize: {
					mutation: true,
					resize: true,
					rootElementSelector: '#root',
				},
			},
			callbacks: {
				onNextStep: () => {},
				onPreviousStep: () => {},
				onGoToStep: () => {},
				onMaximize: () => {},
			},
		},
		placement: 'bottom-start',
	};

	if ( ! showTour ) {
		return null;
	}

	return <WpcomTourKit config={ _.merge( config, extraConfig ) } />;
}

export default SenseiTourKit;
