/**
 * List component from `@woocommerce/components`.
 */

/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import ListItem from './list-item';

/**
 * List component to display a list of items.
 *
 * @param {Object} props props for list
 */
function List( props ) {
	const { className, items, children } = props;
	const listClassName = classnames( 'sensei-list', className );

	return (
		<ul className={ listClassName } role="menu">
			{ items.map( ( item, index ) => {
				const { className: itemClasses, href, key, onClick } = item;
				const hasAction = typeof onClick === 'function' || href;
				const itemClassName = classnames(
					'sensei-list__item',
					itemClasses,
					{
						'has-action': hasAction,
					}
				);

				return (
					<li key={ key || index } className={ itemClassName }>
						{ children ? (
							children( item, index )
						) : (
							<ListItem item={ item } />
						) }
					</li>
				);
			} ) }
		</ul>
	);
}

export default List;
