/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';

let isCourseListBlockSelected = false;

/**
 * Check if a block already exists in the editor.
 *
 * @param {string} clientId The block client id.
 * @return {boolean} Whether the block exists in the editor.
 */
const isBlockAlreadyAddedInEditor = ( clientId ) => {
	return !! document.getElementById( 'block-' + clientId );
};

/**
 * Hide pattern selector controls.
 */
const hideCourseListPatternControls = () => {
	const patternsControlClass =
		'.block-editor-block-pattern-setup__display-controls';
	const controls = document.querySelectorAll( `${ patternsControlClass }` );

	// Hide carousel control button and switch to grid view.
	controls.forEach( ( control ) => {
		const controlButtons = control.querySelectorAll( 'button' );

		// Select Grid view.
		controlButtons[ 1 ].click();

		// Hide all control buttons.
		controlButtons.forEach( ( button ) => {
			button.style.display = 'none';
		} );
	} );
};

/**
 * Hide patterns that are not applicable to the course list.
 */
const hideNonCourseListBlockPatternContainers = () => {
	const patternsClass = '.block-editor-block-pattern-setup-list__list-item';
	const customPatternDescription = 'course-list-element';
	const patterns = document.querySelectorAll( `${ patternsClass }` );

	patterns.forEach( ( pattern ) => {
		const isCourseListPattern = [
			...pattern.querySelectorAll( 'div' ),
		].find( ( e ) => e.innerText === customPatternDescription );
		if ( ! isCourseListPattern ) {
			pattern.style.display = 'none';
		}
	} );
};

/**
 * Hide patterns and settings that aren't applicable for Course List.
 *
 * @param {Function} BlockEdit Block's edit component.
 *
 * @return {Function} Block's edit component.
 */
export const withQueryLoopPatternsAndSettingsHiddenForCourseList = (
	BlockEdit
) => {
	return ( props ) => {
		const isQueryLoopBlock = 'core/query' === props.name;
		const isCourseListBlock =
			isQueryLoopBlock &&
			props?.attributes?.className?.includes(
				'wp-block-sensei-lms-course-list'
			);

		if ( isCourseListBlock && props.isSelected ) {
			isCourseListBlockSelected = true;
		} else if ( props.isSelected ) {
			isCourseListBlockSelected = false;
		}

		// Hide query loop toolbar settings for grid/list outlook.
		if (
			isBlockAlreadyAddedInEditor( props.clientId ) &&
			isCourseListBlockSelected
		) {
			const settingsName = __( 'Grid view', 'sensei-lms' );
			const outlookSettings = document.querySelector(
				`[aria-label="${ settingsName }"]`
			);
			if ( outlookSettings ) {
				const toolbarElement = outlookSettings.parentNode;
				toolbarElement.style.display = 'none';
			}
		}

		// Hide query loop patterns for course list.
		if (
			isCourseListBlockSelected &&
			isQueryLoopBlock &&
			! isCourseListBlock &&
			! isBlockAlreadyAddedInEditor( props.clientId )
		) {
			hideCourseListPatternControls();
			hideNonCourseListBlockPatternContainers();
		}

		return <BlockEdit { ...props } />;
	};
};

addFilter(
	'editor.BlockEdit',
	'sensei-lms/course-list-block',
	withQueryLoopPatternsAndSettingsHiddenForCourseList
);
