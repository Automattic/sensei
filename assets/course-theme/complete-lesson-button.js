/**
 * WordPress dependencies
 */
import domReady from '@wordpress/dom-ready';
import { __ } from '@wordpress/i18n';

/**
 * Initializes complete lesson transition.
 */
export const initCompleteLessonTransition = () => {
	domReady( () => {
		const completeForms = document.querySelectorAll(
			'[data-id="complete-lesson-form"], .lesson_button_form'
		);
		const completeButtons = document.querySelectorAll(
			'[data-id="complete-lesson-button"], .wp-block-sensei-lms-button-complete-lesson button'
		);
		const progressBars = document.querySelectorAll(
			'.sensei-course-theme-course-progress-bar-inner'
		);
		const mainContent = document.querySelector(
			'.sensei-course-theme__main-content'
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

			// Skip if the form is not for complete lesson (Reset lesson block, for example).
			if (
				! form.querySelector(
					'input[name="quiz_action"][value="lesson-complete"]'
				)
			) {
				return;
			}

			delayFormSubmit( e, form );
			runProgressBarAnimation();

			mainContent.insertAdjacentHTML(
				'beforebegin',
				`<div class="sensei-course-theme-lesson-completion-notice">
					<svg width="70" height="70" viewBox="0 0 70 70" fill="none" xmlns="http://www.w3.org/2000/svg" class="sensei-course-theme-lesson-completion-notice__icon">
						<circle cx="35" cy="35" r="34.25" stroke="currentColor" stroke-width="1.5" />
						<path d="M45.1909 25.2503L31.4692 43.7045L23.5125 37.7883" stroke="currentColor" stroke-width="2" />
					</svg>
					<p class="sensei-course-theme-lesson-completion-notice__text">
						${ __( 'Lesson complete', 'sensei-lms' ) }
					</p>
				</div>`
			);
		};

		completeForms.forEach( ( form ) => {
			form.addEventListener( 'submit', onFormSubmit );
		} );
	} );
};
