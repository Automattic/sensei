/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useQueryStringRouter } from '../shared/query-string-router';

const Tabs = () => {
	const { goTo } = useQueryStringRouter();

	return (
		<nav>
			<ul className="subsubsub sensei-extensions__tabs">
				<li>
					<a
						href="#all"
						className="current"
						aria-current="page"
						onClick={ ( e ) => {
							e.preventDefault();
							goTo( 'all' );
						} }
					>
						{ __( 'All', 'sensei-lms' ) }{ ' ' }
						<span className="count">(3)</span>
					</a>
					|
				</li>
				<li>
					<a
						href="#free"
						onClick={ ( e ) => {
							e.preventDefault();
							goTo( 'free' );
						} }
					>
						{ __( 'Free', 'sensei-lms' ) }{ ' ' }
						<span className="count">(3)</span>
					</a>
					|
				</li>
				<li>
					<a
						href="#third-party"
						onClick={ ( e ) => {
							e.preventDefault();
							goTo( 'third-party' );
						} }
					>
						{ __( 'Third party', 'sensei-lms' ) }
					</a>
					|
				</li>
				<li>
					<a
						href="#installed"
						onClick={ ( e ) => {
							e.preventDefault();
							goTo( 'installed' );
						} }
					>
						{ __( 'Installed', 'sensei-lms' ) }
					</a>
				</li>
			</ul>
		</nav>
	);
};

export default Tabs;
