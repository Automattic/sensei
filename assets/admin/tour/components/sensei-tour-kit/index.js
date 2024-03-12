/**
 * External dependencies
 */
import WpcomTourKitMinimized from '@automattic/tour-kit/src/variants/wpcom/components/wpcom-tour-kit-minimized';
import TourKit from '@automattic/tour-kit/src/components/tour-kit';
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
import SenseiTourKitStep from '../sensei-tour-kit-step';

/**
 * Renders a tour kit component for Sensei.
 *
 * @param {Object}     props                  Component props.
 * @param {string}     props.tourName         The unique name of the tour.
 * @param {string}     props.trackId          ID of tracking event (optional). Tracking will be enabled only when provided.
 * @param {TourStep[]} props.steps            An array of steps to include in the tour.
 * @param {Object}     [props.extraConfig={}] Additional configuration options for the tour kit.
 */
function SenseiTourKit( { tourName, trackId, steps, extraConfig = {} } ) {
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
				onMaximize: ( index ) => {
					performStepAction( index, steps );
				},
				onMinimize: () => {
					removeHighlightClasses();
				},
				onStepViewOnce: trackTourStepView,
			},
		},
		placement: 'bottom-start',
		renderers: {
			tourStep: SenseiTourKitStep,
			tourMinimized: WpcomTourKitMinimized,
		},
	};

	if ( ! showTour ) {
		return null;
	}

	return (
		<TourKit
			__temp__className={ 'wpcom-tour-kit' }
			config={ _.merge( config, extraConfig ) }
		/>
	);
}

export default SenseiTourKit;
