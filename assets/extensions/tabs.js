/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

const Tabs = () => (
	<nav>
		<ul className="subsubsub sensei-extensions__tabs">
			<li>
				<a href="#all" className="current" aria-current="page">
					{ __( 'All', 'sensei-lms' ) }{ ' ' }
					<span className="count">(3)</span>
				</a>
				|
			</li>
			<li>
				<a href="#free">
					{ __( 'Free', 'sensei-lms' ) }{ ' ' }
					<span className="count">(3)</span>
				</a>
				|
			</li>
			<li>
				<a href="#third-party">{ __( 'Third party', 'sensei-lms' ) }</a>
				|
			</li>
			<li>
				<a href="#installed">{ __( 'Installed', 'sensei-lms' ) }</a>
			</li>
		</ul>
	</nav>
);

export default Tabs;
