/**
 * WordPress dependencies
 */
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ExitSurveyForm } from './form';

( function senseiExitSurvey() {
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
			let container = document.querySelector( '#sensei-exit-survey' );
			if ( ! container ) {
				container = document.createElement( 'div' );
				container.setAttribute( 'id', 'sensei-exit-survey-modal' );
				document.body.appendChild( container );
			}

			this.container = container;

			render(
				<ExitSurveyForm
					submit={ this.submitExitSurvey }
					skip={ this.closeAndDeactivate }
				/>,
				container
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
			this.container.remove();
			window.location = this.href;
		};
	}

	addExitSurveyOnDeactivate();
} )();
