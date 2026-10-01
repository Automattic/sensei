/**
 * Internal dependencies
 */
import './sensei-notice-dismiss';

describe( 'Sensei notice tasks', () => {
	beforeEach( () => {
		document.body.innerHTML = `
			<div class="sensei-notice" data-sensei-notice-id="review-question">
				<a data-sensei-notice-tasks='[{"type":"hide","notice_id":"review-question"},{"type":"show","notice_id":"review-follow-up"}]'>
					<span>Yes</span>
				</a>
			</div>
			<div class="sensei-notice sensei-notice--is-hidden" data-sensei-notice-id="review-follow-up"></div>
		`;
	} );

	it( 'Clicking a child of a task element runs its notice tasks', () => {
		const reviewFollowUp = document.querySelector(
			'[data-sensei-notice-id="review-follow-up"]'
		);

		document.querySelector( 'span' ).click();

		expect( reviewFollowUp ).not.toHaveClass( 'sensei-notice--is-hidden' );
	} );
} );
