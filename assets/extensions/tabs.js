/**
 * Internal dependencies
 */
import { useQueryStringRouter } from '../shared/query-string-router';

const Tabs = ( { tabs } ) => {
	const { currentRoute, goTo } = useQueryStringRouter();

	return (
		<nav>
			<ul className="subsubsub sensei-extensions__tabs">
				{ tabs.map( ( { id, label, count } ) => (
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
							{ label }
							<span className="sensei-extensions__tabs__count count">
								({ count })
							</span>
						</a>
					</li>
				) ) }
			</ul>
		</nav>
	);
};

export default Tabs;
