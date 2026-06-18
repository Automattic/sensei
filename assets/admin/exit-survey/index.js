/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ExitSurveyForm } from './form';

( function senseiExitSurvey() {
	/**
	 * Id of the container the survey is mounted into.
	 */
	const CONTAINER_ID = 'sensei-exit-survey-modal';

	/**
	 * The single React root for the survey, reused across opens.
	 *
	 * @type {?Object}
	 */
	let surveyRoot = null;

	/**
	 * Add exit survey modal when clicking the Deactivate link for Sensei LMS plugin.
	 */
	const addExitSurveyOnDeactivate = () => {
		const getDeactivateLinkElement = ( slug ) =>
			document.querySelector(
				`#the-list [data-slug="${ slug }"] span.deactivate a`
			);

		const deactivateLinks = [
			getDeactivateLinkElement( 'sensei-lms' ),
			getDeactivateLinkElement( 'sensei-pro-wc-paid-courses' ),
			getDeactivateLinkElement( 'sensei-with-woocommerce-paid-courses' ),
			getDeactivateLinkElement(
				'woocommerce-com-woocommerce-paid-courses'
			),
		].filter( ( e ) => !! e );

		deactivateLinks.forEach( ( link ) => {
			link.addEventListener( 'click', ( event ) => {
				event.preventDefault();

				new ExitSurveyModal( {
					href: event.target.href,
				} ).open();
			} );
		} );
	};

	/**
	 * Exit survey modal.
	 */
	class ExitSurveyModal {
		href;
		container;
		root;

		/**
		 * Exit survey constructor.
		 *
		 * @param {string} href Link to deactivate plugin.
		 */
		constructor( { href } ) {
			this.href = href;
		}

		/**
		 * Create and open a modal with an exit survey form.
		 *
		 */
		open = () => {
			let container = document.getElementById( CONTAINER_ID );
			if ( ! container ) {
				container = document.createElement( 'div' );
				container.setAttribute( 'id', CONTAINER_ID );
				document.body.appendChild( container );
			}

			this.container = container;

			// Reuse a single root so repeated opens don't mount twice
			// into the same container.
			if ( ! surveyRoot ) {
				surveyRoot = createRoot( container );
			}
			this.root = surveyRoot;
			this.root.render(
				<ExitSurveyForm
					submit={ this.submitExitSurvey }
					skip={ this.closeAndDeactivate }
				/>
			);
		};

		/**
		 * Submit exit survey to AJAX endpoint.
		 *
		 * @param {Object} data
		 */
		submitExitSurvey = async ( data ) => {
			const body = new window.FormData();

			body.append( 'action', 'exit_survey' );
			body.append( '_wpnonce', window.sensei_exit_survey?.nonce );
			body.append( 'reason', data.reason );
			body.append( 'details', data.details );

			// Get the name of the active theme.
			try {
				const result = await apiFetch( {
					path: '/wp/v2/themes?status=active',
				} );

				if ( result.length > 0 ) {
					body.append( 'theme', result[ 0 ].name?.raw || '' );
				}
			} catch ( e ) {}

			await window.fetch( window.ajaxurl, {
				method: 'POST',
				body,
			} );

			this.closeAndDeactivate();
		};

		/**
		 * Close survey modal and continue plugin deactivation.
		 */
		closeAndDeactivate = () => {
			this.root?.unmount();
			surveyRoot = null;
			this.container.remove();
			window.location = this.href;
		};
	}

	addExitSurveyOnDeactivate();
} )();
