/**
 * WordPress dependencies
 */
import domReady from '@wordpress/dom-ready';
import { __ } from '@wordpress/i18n';

export const initCompleteLessonTransition = () => {
	domReady( () => {
		const completeForms = document.querySelectorAll(
			'[data-id="complete-lesson-form"], .lesson_button_form'
		);
		const completeButtons = document.querySelectorAll(
			'[data-id="complete-lesson-button"], .wp-block-sensei-lms-button-complete-lesson button'
		);
		const mainContent = document.querySelector(
			'.sensei-course-theme__main-content'
		);

		const disableButtons = () => {
			completeButtons.forEach( ( button ) => {
				button.setAttribute( 'disabled', 'disabled' );
				button.classList.add( 'is-busy' );
			} );
		};

		completeForms.forEach( ( form ) => {
			form.addEventListener( 'submit', ( e ) => {
				// Skip if the form is not for complete lesson (Reset lesson block, for example).
				if (
					! form.querySelector(
						'input[name="quiz_action"][value="lesson-complete"]'
					)
				) {
					return;
				}

				e.preventDefault();
				disableButtons();

				setTimeout( () => {
					form.submit();
				}, 1000 );

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
			} );
		} );
	} );
};
