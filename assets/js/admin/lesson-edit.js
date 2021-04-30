/**
 * WordPress dependencies
 */
import { select, dispatch } from '@wordpress/data';

( () => {
	const blockEditorSelector = select( 'core/block-editor' );
	const editPostSelector = select( 'core/edit-post' );
	const editPostDispatcher = dispatch( 'core/edit-post' );

	/**
	 * Toggle Lesson Information metabox depending on whether the Lesson Properties block has been
	 * added to the lesson.
	 */
	window.sensei_toggle_legacy_lesson_metaboxes = () => {
		if ( ! blockEditorSelector ) {
			return;
		}

		const metaboxName = 'meta-box-lesson-info';
		const lessonPropertiesBlockCount = blockEditorSelector.getGlobalBlockCount(
			'sensei-lms/lesson-properties'
		);
		const enable = lessonPropertiesBlockCount === 0;

		if ( enable !== editPostSelector.isEditorPanelEnabled( metaboxName ) ) {
			editPostDispatcher.toggleEditorPanelEnabled( metaboxName );
		}

		// Don't submit lesson length and complexity values in metaboxes.
		document
			.querySelectorAll( '#lesson-info input, #lesson-info select' )
			.forEach( ( input ) => {
				input.disabled = lessonPropertiesBlockCount > 0;
			} );
	};
} )();

jQuery( document ).ready( function () {
	window.sensei_toggle_legacy_lesson_metaboxes();

	// Lessons Write Panel.
	const complexityOptionElements = jQuery( '#lesson-complexity-options' );
	if ( complexityOptionElements.length > 0 ) {
		complexityOptionElements.select2( { width: 'resolve' } );
	}

	const prerequisiteOptionElements = jQuery( '#lesson-prerequisite-options' );
	if ( prerequisiteOptionElements.length > 0 ) {
		prerequisiteOptionElements.select2( {
			width: 'resolve',
		} );
	}

	const courseOptionElements = jQuery( '#lesson-course-options' );
	if ( courseOptionElements.length > 0 ) {
		courseOptionElements.select2( { width: 'resolve' } );
	}

	const moduleOptionElements = jQuery( '#lesson-module-options' );
	if ( moduleOptionElements.length > 0 ) {
		moduleOptionElements.select2( { width: 'resolve' } );
	}
} );
