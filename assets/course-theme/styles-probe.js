/**
 * Probe base colors and update Sensei theme variables when they change.
 */
export const StylesProbe = () => {
	let root, link;
	let styles = {};

	function setup() {
		const editorDocument =
			document.querySelector( '.edit-site-visual-editor__editor-canvas' )
				?.contentDocument || document;
		root = editorDocument?.querySelector( '.editor-styles-wrapper' );

		if ( ! root ) {
			return;
		}

		link = editorDocument.createElement( 'a' );
		Object.assign( link.attributes, {
			tabindex: '-1',
			id: 'sensei-theme-style-probe',
		} );
		Object.assign( link.style, { position: 'fixed', top: '-100vh' } );
		root.appendChild( link );
	}

	function getChangedStyles() {
		const { getComputedStyle } = window;
		const { color, backgroundColor } = getComputedStyle( root );
		const { color: linkColor } = getComputedStyle( link );

		const updates = {
			'--sensei-primary-color': linkColor,
			'--sensei-text-color': color,
			'--sensei-background-color': backgroundColor,
			'--sensei-primary-contrast-color': backgroundColor,
		};

		for ( const name in { ...updates } ) {
			if ( styles[ name ] === updates[ name ] ) {
				delete updates[ name ];
			}
		}

		styles = { ...styles, ...updates };

		return updates;
	}

	function setVariables( updates ) {
		for ( const [ name, value ] of Object.entries( updates ) ) {
			root.style.setProperty( name, value );
		}
	}

	function update() {
		if ( ! root || ! root.isConnected ) {
			setup();
		}

		if ( root ) {
			setVariables( getChangedStyles() );
		}
	}

	return { update };
};

const probe = StylesProbe();
setInterval( probe.update, 1000 );
