/**
 * WordPress dependencies
 */
import {
	store as blockEditorStore,
	BlockPreview,
} from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { ENTER, SPACE } from '@wordpress/keycodes';
import { Fragment, useEffect, useRef, useState } from '@wordpress/element';
import { Spinner } from '@wordpress/components';

/**
 * Patterns list component.
 *
 * @param {Object}   props          Component props.
 * @param {Function} props.onChoose Callback on choosing a pattern.
 */
const PatternsList = ( { onChoose } ) => {
	const container = useRef();
	const [ loading, updateLoaded ] = useLoadingStatus( container );
	return (
		<Fragment>
			<div
				className="sensei-patterns-list__loading"
				style={ { display: loading ? 'flex' : 'none' } }
			>
				<h2>{ __( 'Loading…', 'sensei-lms' ) }</h2>
				<Spinner />
			</div>
			<div ref={ container } onLoadCapture={ updateLoaded }>
				<InternalPatternsList onChoose={ onChoose } />
			</div>
		</Fragment>
	);
};

/**
 * Monitors the given container trying to detect when all the iframes on that container are loaded
 *
 * @param {{current:HTMLElement}} container The container to monitor for the loading status
 * @return {Array.<boolean|Function>} If the container is fully loaded and an event handler to check for loaded status
 */
const useLoadingStatus = ( container ) => {
	const [ loading, setLoading ] = useState( true );
	const updateLoaded = useDetectLoaded( container, () =>
		setLoading( false )
	);
	useEffect( () => {
		if ( ! container.current ) {
			return;
		}
		// eslint-disable-next-line no-undef
		const observer = new MutationObserver( updateLoaded );
		observer.observe( container.current, {
			subtree: true,
			childList: true,
			attributes: true,
		} );
		return () => observer.disconnect();
	}, [ container, updateLoaded ] );
	return [ loading, updateLoaded ];
};

/**
 * Returns a function that can be used to detect if all the iframes are loaded and uses that to hide the loading status.
 *
 * @param {{current:HTMLElement}} container The element used as container for the iframes that will be checked.
 * @param {Function}              setLoaded The function to call when the container is fully 'loaded'.
 * @return {Function} A Function that detects if the content in the container is loaded and calls setLoaded if yes.
 */
const useDetectLoaded = ( container, setLoaded ) => {
	const timeoutId = useRef();
	return () => {
		const iframes = Array.from(
			container.current.querySelectorAll( 'iframe' )
		);
		const isLoaded = iframes.every( isIframeLoaded );
		if ( isLoaded ) {
			if ( timeoutId.current ) {
				clearTimeout( timeoutId.current );
			}
			timeoutId.current = setTimeout( setLoaded, 500 );
		}
	};
};

/**
 * Returns if the readyState of the iframe is complete (which means the iframe has loaded everything).
 *
 * @param {HTMLIFrameElement} iframe The iframe to analyze.
 * @return {boolean} If the readyState of the iframe is 'complete' or not.
 */
const isIframeLoaded = ( iframe ) => {
	const doc = iframe.contentDocument || iframe.contentWindow.document;
	return doc.readyState === 'complete';
};

/**
 * Internal Patterns list component (without caring a lot about loading status, for instance).
 *
 * @param {Object}   props          Component props.
 * @param {Function} props.onChoose Callback on choosing a pattern.
 */
const InternalPatternsList = ( { onChoose } ) => {
	const { patterns } = useSelect( ( select ) => ( {
		patterns: select( blockEditorStore ).__experimentalGetAllowedPatterns(),
	} ) );

	return (
		<div
			className="sensei-patterns-list"
			role="listbox"
			aria-label={ __( 'Sensei block patterns', 'sensei-lms' ) }
		>
			{ patterns
				.filter(
					( { categories } ) =>
						categories && categories.includes( 'sensei-lms' )
				)
				.map(
					( { name, title, description, blocks, viewportWidth } ) => (
						// eslint-disable-next-line jsx-a11y/click-events-have-key-events, jsx-a11y/no-static-element-interactions
						<div
							key={ name }
							className="sensei-patterns-list__item"
							title={ description }
							role="option"
							tabIndex={ 0 }
							{ ...accessibleClick( () => {
								onChoose( blocks );
							} ) }
						>
							<div className="sensei-patterns-list__item-preview">
								<BlockPreview
									blocks={ blocks }
									viewportWidth={ viewportWidth }
								/>
							</div>
							<div className="sensei-patterns-list__item-title">
								{ title }
							</div>
						</div>
					)
				) }
		</div>
	);
};

/**
 * It returns events to fire the click event on click, pressing enter, and pressing space.
 *
 * @param {Function} fn Click callback.
 *
 * @return {Object} Object with props to add in the React element.
 */
const accessibleClick = ( fn ) => ( {
	onClick: fn,
	onKeyUp: ( e ) => {
		if ( [ ENTER, SPACE ].includes( e.keyCode ) ) {
			fn( e );
		}
	},
} );

export default PatternsList;
