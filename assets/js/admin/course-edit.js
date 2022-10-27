/**
 * WordPress dependencies
 */
import { select, subscribe, dispatch, useSelect } from '@wordpress/data';
import { applyFilters } from '@wordpress/hooks';
import domReady from '@wordpress/dom-ready';
import { registerPlugin, getPlugin } from '@wordpress/plugins';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { startBlocksTogglingControl } from './blocks-toggling-control';
import CourseTheme from './course-theme';
import CourseVideoSidebar from './course-video-sidebar';
import CoursePricingPromoSidebar from './course-pricing-promo-sidebar';
import CourseAccessPeriodPromoSidebar from './course-access-period-promo-sidebar';
import {
	extractStructure,
	getFirstBlockByName,
} from '../../blocks/course-outline/data';

import SenseiIcon from '../../icons/logo-tree.svg';
import {
	PluginDocumentSettingPanel,
	PluginSidebar,
	PluginSidebarMoreMenuItem,
} from '@wordpress/edit-post';

const pluginSidebarHandle = 'sensei-lms-course-settings-sidebar';
const pluginDocumentHandle = 'sensei-lms-document-settings-sidebar';

( () => {
	const editPostSelector = select( 'core/edit-post' );
	const coreEditor = select( 'core/editor' );
	const teacherIdSelect = document.getElementsByName(
		'sensei-course-teacher-author'
	);
	const slugBearer = document.getElementsByName(
		'course_module_custom_slugs'
	);
	if ( editPostSelector && teacherIdSelect.length ) {
		let isSavingMetaboxes = false;

		subscribe( () => {
			const isSavingPost =
				coreEditor.isSavingPost() && ! coreEditor.isAutosavingPost();
			if (
				isSavingPost &&
				! editPostSelector.isSavingMetaBoxes() &&
				slugBearer
			) {
				const outlineBlock = getFirstBlockByName(
					'sensei-lms/course-outline',
					select( 'core/block-editor' ).getBlocks()
				);
				const moduleSlugs =
					outlineBlock &&
					extractStructure( outlineBlock.innerBlocks )
						.filter( ( block ) => block.slug )
						.map( ( block ) => block.slug );
				slugBearer[ 0 ].value = JSON.stringify( moduleSlugs );
			}
			if ( editPostSelector.isSavingMetaBoxes() !== isSavingMetaboxes ) {
				isSavingMetaboxes = editPostSelector.isSavingMetaBoxes();

				if ( ! isSavingMetaboxes ) {
					const currentTeacherId = teacherIdSelect[ 0 ].value;
					if ( currentTeacherId ) {
						document.getElementsByName(
							'post_author_override'
						)[ 0 ].value = currentTeacherId;
					}
				}
			}
		} );
	}
} )();

domReady( () => {
	startBlocksTogglingControl( 'course' );

	jQuery( '#course-prerequisite-options' ).select2( { width: '100%' } );

	const trackLinkClickCallback = ( event_name ) => ( e ) => {
		var properties = {
			course_status: e.target.dataset.courseStatus,
		};

		// Get course status from post state if it's available.
		if ( wp.data && wp.data.select( 'core/editor' ) ) {
			properties.course_status = wp.data
				.select( 'core/editor' )
				.getCurrentPostAttribute( 'status' );
		}

		sensei_log_event( event_name, properties );
	};

	// Log when the "Add Lesson" link is clicked.
	document
		.querySelector( 'a.add-course-lesson' )
		?.addEventListener(
			'click',
			trackLinkClickCallback( 'course_add_lesson_click' )
		);

	// Log when the "Edit Lesson" link is clicked.
	document
		.querySelector( 'a.edit-lesson-action' )
		?.addEventListener(
			'click',
			trackLinkClickCallback( 'course_edit_lesson_click' )
		);
} );

/**
 * Plugins
 */

const CourseSidebar = () => {
	return (
		<>
			<PluginSidebarMoreMenuItem
				target={ pluginSidebarHandle }
				icon={ <SenseiIcon height="20" width="20" color="#43AF99" /> }
			>
				{ __( 'Sensei Settings', 'sensei-lms' ) }
			</PluginSidebarMoreMenuItem>
			<PluginSidebar
				name={ pluginSidebarHandle }
				title={ __( 'Sensei Settings', 'sensei-lms' ) }
				icon={ <SenseiIcon color="#43AF99" /> }
			></PluginSidebar>
		</>
	);
};

registerPlugin( pluginSidebarHandle, {
	render: CourseSidebar,
} );

const SenseiSettingsDocumentSidebar = () => {
	const isSenseiEditorPanelOpen = useSelect( ( select ) => {
		return select( 'core/edit-post' ).isEditorPanelOpened(
			`${ pluginDocumentHandle }/${ pluginDocumentHandle }`
		);
	} );
	if ( isSenseiEditorPanelOpen ) {
		// when 'Sensei Settings' is clicked, isSenseiEditorPanelOpen returns true, so we open the 'Sensei Settings'
		// plugin sidebar and then close the 'Sensei Settings' panel which sets isSenseiEditorPanelOpen back to false.
		dispatch( 'core/edit-post' ).openGeneralSidebar(
			`${ pluginSidebarHandle }/${ pluginSidebarHandle }`
		);
		dispatch( 'core/edit-post' ).toggleEditorPanelOpened(
			`${ pluginDocumentHandle }/${ pluginDocumentHandle }`
		);
	}
	return (
		<PluginDocumentSettingPanel
			name={ pluginDocumentHandle }
			title={ __( 'Sensei Settings ', 'sensei-lms' ) }
		></PluginDocumentSettingPanel>
	);
};

registerPlugin( pluginDocumentHandle, {
	render: SenseiSettingsDocumentSidebar,
	icon: null,
} );

/**
 * Filters the course pricing sidebar toggle.
 *
 * @since 4.1.0
 *
 * @hook  senseiCoursePricingHide     Hook used to hide course pricing promo sidebar.
 *
 * @param {boolean} hideCoursePricing Boolean value that defines if the course pricing promo sidebar should be hidden.
 * @return {boolean}                  Returns a boolean value that defines if the course pricing promo sidebar should be hidden.
 */
if ( ! applyFilters( 'senseiCoursePricingHide', false ) ) {
	registerPlugin( 'sensei-course-pricing-promo-sidebar', {
		render: CoursePricingPromoSidebar,
		icon: null,
	} );
}

/**
 * Filters the course access period display.
 *
 * @since 4.1.0
 *
 * @param {boolean} hideCourseAccessPeriod Whether to hide the access period.
 * @return {boolean} Whether to hide the access period.
 */
if ( ! applyFilters( 'senseiCourseAccessPeriodHide', false ) ) {
	registerPlugin( 'sensei-course-access-period-promo-plugin', {
		render: CourseAccessPeriodPromoSidebar,
		icon: null,
	} );
}

registerPlugin( 'sensei-course-theme-plugin', {
	render: CourseTheme,
	icon: null,
} );

registerPlugin( 'sensei-course-video-progression-plugin', {
	render: CourseVideoSidebar,
	icon: null,
} );
