/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Grid component.
 *
 * @param {Object} props           Component props.
 * @param {Array}  props.as        Tag or component to render as wrapper.
 * @param {Array}  props.className Class name to be added in the wrapper.
 * @param {Object} props.children  Children.
 */
export const Grid = ( { as: Component = 'div', className, children } ) => (
	<Component className={ classnames( className, 'sensei-extensions__grid' ) }>
		{ children }
	</Component>
);

/**
 * Col component (should be used inside the Grid).
 *
 * @param {Object} props           Component props.
 * @param {Array}  props.as        Tag or component to render as wrapper.
 * @param {Array}  props.className Class name to be added in the wrapper.
 * @param {Array}  props.cols      Number of columns to use.
 * @param {Object} props.children  Children.
 */
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
