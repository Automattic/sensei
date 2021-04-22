/**
 * External dependencies
 */
import classnames from 'classnames';

export const Grid = ( { as: Component = 'div', className, children } ) => (
	<Component className={ classnames( className, 'sensei-extensions__grid' ) }>
		{ children }
	</Component>
);

export const Col = ( {
	as: Component = 'div',
	className,
	cols = 12,
	children,
} ) => (
	<Component
		className={ classnames(
			className,
			'sensei-extensions__grid__col',
			`--col-${ cols }`
		) }
	>
		{ children }
	</Component>
);
