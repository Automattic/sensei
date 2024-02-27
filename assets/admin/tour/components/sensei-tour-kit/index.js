/**
 * External dependencies
 */
import { WpcomTourKit } from '@automattic/tour-kit';
import _ from 'lodash';

/**
 * WordPress dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { SENSEI_TOUR_STORE } from '../../data/store';
import { TourStep } from '../../types';

/**
 * Renders a tour kit component using Sensei.
 *
 * @param {Object}     props                  - Component props.
 * @param {string}     props.tourName         - The unique name of the tour.
 * @param {TourStep[]} props.steps            - An array of steps to include in the tour.
 * @param {Object}     [props.extraConfig={}] - Additional configuration options for the tour kit.
 */
function SenseiTourKit( { tourName, steps, extraConfig = {} } ) {
	const { showTour } = useSelect( ( select ) => {
		const { getIfShowTour } = select( SENSEI_TOUR_STORE );
		return {
			showTour: getIfShowTour(),
		};
	} );

	const { setTourShowStatus } = useDispatch( SENSEI_TOUR_STORE );

	const config = {
		steps,
		closeHandler: () => setTourShowStatus( false, true, tourName ),
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
