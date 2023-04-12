/**
 * WordPress dependencies
 */
import domReady from '@wordpress/dom-ready';

/**
 * Initializes complete lesson transition.
 */
export const initCompleteLessonTransition = () => {
	domReady( () => {
		const completeForms = document.querySelectorAll(
			'[data-id="complete-lesson-form"]'
		);
		const completeButtons = document.querySelectorAll(
			'[data-id="complete-lesson-button"]'
		);
		const progressBars = document.querySelectorAll(
			'.sensei-course-theme-course-progress-bar-inner'
		);

		/**
		 * Disable complete buttons.
		 */
		const disableButtons = () => {
			completeButtons.forEach( ( button ) => {
				button.setAttribute( 'disabled', 'disabled' );
				button.classList.add( 'is-busy' );
			} );
		};

		/**
		 * Delay submit to show animations.
		 *
		 * @param {Object} e    The submit event.
		 * @param {Object} form The form DOM node.
		 */
		const delayFormSubmit = ( e, form ) => {
			e.preventDefault();
			disableButtons();

			setTimeout( () => {
				form.submit();
			}, 1000 );
		};

		/**
		 * Run progress bar animation.
		 */
		const runProgressBarAnimation = () => {
			progressBars.forEach( ( progressBar ) => {
				const { completed, count } = progressBar.dataset;

				// Percentage with one more completed.
				const percentage = ( ( +completed + 1 ) / +count ) * 100;
				progressBar.style.width = `${ percentage }%`;
			} );
		};

		/**
		 * Form submit handler.
		 *
		 * @param {Object} e The submit event.
		 */
		const onFormSubmit = ( e ) => {
			const form = e.target;

			delayFormSubmit( e, form );
			runProgressBarAnimation();
		};

		completeForms.forEach( ( form ) => {
			form.addEventListener( 'submit', onFormSubmit );
		} );
	} );
};
