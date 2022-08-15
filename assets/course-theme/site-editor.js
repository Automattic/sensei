/**
 * Set the editor iframe to height: 100% to support Learning Mode templates.
 *
 */
export function updateIframeHeight() {
	const canvas = document.querySelector(
		'.edit-site-visual-editor__editor-canvas'
	);

	canvas?.contentDocument.documentElement.style.setProperty(
		'height',
		'100%'
	);
}
