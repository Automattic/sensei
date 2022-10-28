/**
 * WordPress dependencies
 */
import { select, subscribe, dispatch, useSelect } from '@wordpress/data';
import { applyFilters } from '@wordpress/hooks';
import domReady from '@wordpress/dom-ready';
import { registerPlugin } from '@wordpress/plugins';
import { __ } from '@wordpress/i18n';
import {
	PluginDocumentSettingPanel,
	PluginSidebar,
	PluginSidebarMoreMenuItem,
} from '@wordpress/edit-post';
import { Slot } from '@wordpress/components';

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
	const hideCoursePricing = applyFilters( 'senseiCoursePricingHide', false );
	const hideAccessPeriod = applyFilters(
		'senseiCourseAccessPeriodHide',
		false
	);
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
				position={ 1 }
			>
				{ ! hideCoursePricing && <CoursePricingPromoSidebar /> }
				{ ! hideAccessPeriod && <CourseAccessPeriodPromoSidebar /> }
				<Slot name="SenseiCourseSidebar" />
				<CourseTheme />
				<CourseVideoSidebar />
			</PluginSidebar>
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
