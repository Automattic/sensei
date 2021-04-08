/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useQueryStringRouter } from '../shared/query-string-router';

const tabs = [
	{
		id: 'all',
		label: __( 'All', 'sensei-lms' ),
	},
	{
		id: 'free',
		label: __( 'Free', 'sensei-lms' ),
	},
	{
		id: 'third-party',
		label: __( 'Third party', 'sensei-lms' ),
	},
	{
		id: 'installed',
		label: __( 'Installed', 'sensei-lms' ),
	},
];

const Tabs = () => {
	const { currentRoute, goTo } = useQueryStringRouter();

	return (
		<nav>
			<ul className="subsubsub sensei-extensions__tabs">
				{ tabs.map( ( { id, label } ) => (
					<li key={ id } className="sensei-extensions__tabs__tab">
						<a
							href={ `#${ id }-extensions` }
							onClick={ ( e ) => {
								e.preventDefault();
								goTo( id );
							} }
							{ ...( currentRoute === id && {
								className: 'current',
								'aria-current': 'page',
							} ) }
						>
							{ label } <span className="count">(3)</span>
						</a>
					</li>
				) ) }
			</ul>
		</nav>
	);
};

export default Tabs;
