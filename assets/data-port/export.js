/**
 * WordPress dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import SenseiExportPage from './export/index';

const root = createRoot( document.getElementById( 'sensei-export-page' ) );
root.render( <SenseiExportPage /> );
