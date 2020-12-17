import { render } from '@wordpress/element';
import { SenseiExtensionInstall, withInstaller } from './extensions.component';

/**
 * Replace install buttons on the Extensions page with interactive components.
 */
function attachSenseiExtensionInstallComponents() {
	document
		.querySelectorAll( '.sensei-extension-installer' )
		.forEach( ( node ) => {
			const props = [ 'slug', 'source' ].reduce( ( m, name ) => {
				m[ name ] = node.getAttribute( `data-${ name }` );
				return m;
			}, {} );

			props.errorContainer = node.parentNode;
			const Installer = withInstaller( SenseiExtensionInstall );
			render( <Installer { ...props } />, node );
		} );
}

attachSenseiExtensionInstallComponents();
