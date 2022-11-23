/**
 * Prompt opening a file from the given url.
 *
 * @param {Object} options
 * @param {string} options.url    The file URL.
 * @param {string} [options.name] Filename for the downloaded file.
 */
export function downloadFile( { url, name } ) {
	const link = document.createElement( 'a' );
	link.href = url;
	link.download = name || true;
	document.body.appendChild( link );
	link.click();
	document.body.removeChild( link );
}
