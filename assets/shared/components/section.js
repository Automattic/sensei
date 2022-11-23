/**
 * Section and Header helper component from `@woocommerce/components`.
 */

/**
 * WordPress dependencies
 */
import { createContext } from '@wordpress/element';

/**
 * Context container for heading level. We start at 2 because the `h1` assumed to be the page header.
 */
const Level = createContext( 2 );

/**
 * These components are used to frame out the page content for accessible heading hierarchy. Instead of defining fixed heading levels
 * (`h2`, `h3`, …) you can use `<H />` to create "section headings", which look to the parent `<Section />`s for the appropriate
 * heading level.
 *
 * @param {Object} props -
 * @return {Object} -
 */
export function H( props ) {
	return (
		<Level.Consumer>
			{ ( level ) => {
				const Heading = 'h' + Math.min( level, 6 );
				return <Heading { ...props } />;
			} }
		</Level.Consumer>
	);
}

/**
 * The section wrapper, used to indicate a sub-section (and change the header level context).
 *
 * @param {Object} props
 * @param {string} props.component
 * @param {Node}   props.children
 * @return {Object} -
 */
export function Section( { component, children, ...props } ) {
	const Component = component || 'div';
	return (
		<Level.Consumer>
			{ ( level ) => (
				<Level.Provider value={ level + 1 }>
					{ component === false ? (
						children
					) : (
						<Component { ...props }>{ children }</Component>
					) }
				</Level.Provider>
			) }
		</Level.Consumer>
	);
}
