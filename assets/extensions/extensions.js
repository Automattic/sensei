/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Card from './card';

const Extensions = () => (
	<>
		<section>
			<h2>{ __( 'Featured', 'sensei-lms' ) }</h2>
			<ul>
				<li>
					<Card hasUpdate />
				</li>
				<li>
					<Card />
				</li>
				<li>
					<Card />
				</li>
			</ul>
		</section>

		<section>
			<h2>{ __( 'Course creation', 'sensei-lms' ) }</h2>
			<ul>
				<li>
					<Card />
				</li>
				<li>
					<Card />
				</li>
				<li>
					<Card />
				</li>
			</ul>
		</section>

		<section>
			<h2>{ __( 'Learner engagement', 'sensei-lms' ) }</h2>
			<ul>
				<li>
					<Card />
				</li>
				<li>
					<Card />
				</li>
				<li>
					<Card />
				</li>
			</ul>
		</section>
	</>
);

export default Extensions;
