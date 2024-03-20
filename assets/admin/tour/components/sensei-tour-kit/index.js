/**
 * External dependencies
 */
import _ from 'lodash';

/**
 * WordPress dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { SENSEI_TOUR_STORE } from '../../data/store';
import { TourStep } from '../../types';
import { performStepAction, removeHighlightClasses } from '../../helper';
import { WpcomTourKit } from '@automattic/tour-kit';

/**
 * Renders a tour kit component for Sensei.
 *
 * @param {Object}     props                  Component props.
 * @param {string}     props.tourName         The unique name of the tour.
 * @param {string}     props.trackId          ID of tracking event (optional). Tracking will be enabled only when provided.
 * @param {TourStep[]} props.steps            An array of steps to include in the tour.
 * @param {Function}   [props.beforeEach]     A function to run before each step.
 * @param {Object}     [props.extraConfig={}] Additional configuration options for the tour kit.
 */
function SenseiTourKit( {
	tourName,
	trackId,
	steps,
	beforeEach = () => {},
	extraConfig = {},
} ) {
	const { showTour } = useSelect( ( select ) => {
		const { shouldShowTour } = select( SENSEI_TOUR_STORE );
		return {
			showTour: shouldShowTour(),
		};
	} );

	const { setTourShowStatus } = useDispatch( SENSEI_TOUR_STORE );

	const trackTourStepView = useCallback(
		( index ) => {
			if ( ! trackId ) {
				return;
			}

			if ( index < steps.length ) {
				const step = steps[ index ];
				window.sensei_log_event( trackId, {
					step: step.slug,
				} );
			}
		},
		[ trackId, steps ]
	);

	const runAction = ( index ) => {
		beforeEach( steps[ index ] );
		performStepAction( index, steps );
	};

	const config = {
		steps,
		closeHandler: () => {
			removeHighlightClasses();
			setTourShowStatus( false, true, tourName );
		},
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
				onNextStep: ( index ) => {
					runAction( index + 1 );
				},
				onPreviousStep: ( index ) => {
					runAction( index - 1 );
				},
				onGoToStep: ( index ) => {
					if ( index === steps.length - 1 ) {
						runAction( 0 );
					} else {
						removeHighlightClasses();
					}
				},
				onMaximize: ( index ) => {
					runAction( index );
				},
				onMinimize: () => {
					removeHighlightClasses();
				},
				onStepViewOnce: ( index ) => {
					if ( index === 0 ) {
						runAction( index );
					}
					trackTourStepView( index );
				},
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
