/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { forwardRef } from '@wordpress/element';

/**
 * Grid component.
 *
 * @param {Object} props           Component props.
 * @param {Array}  props.as        Tag or component to render as wrapper.
 * @param {Array}  props.className Class name to be added in the wrapper.
 * @param {Object} props.children  Children.
 */
export const Grid = ( { as: Component = 'div', className, children } ) => (
	<Component className={ classnames( className, 'sensei-home__grid' ) }>
		{ children }
	</Component>
);

/**
 * Col component (should be used inside the Grid).
 *
 * @param {Object}       props           Component props.
 * @param {Array}        props.as        Tag or component to render as wrapper.
 * @param {Array|string} props.className Class name to be added in the wrapper.
 * @param {number}       props.cols      Number of columns to use.
 * @param {Object}       props.children  Children.
 * @param {Object}       ref             Component ref.
 */
export const Col = forwardRef(
	(
		{ as: Component = 'div', className, cols = 12, children, ...props },
		ref
	) => (
		<Component
			className={ classnames(
				className,
				'sensei-home__grid__col',
				`--col-${ cols }`
			) }
			{ ...props }
			ref={ ref }
		>
			{ children }
		</Component>
	)
);
