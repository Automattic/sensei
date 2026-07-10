/**
 * WordPress dependencies
 */
import { applyFilters } from '@wordpress/hooks';
import {
	store as editPostStore,
	PluginDocumentSettingPanel as DeprecatedPluginDocumentSettingPanel,
	PluginSidebar as DeprecatedPluginSidebar,
	PluginSidebarMoreMenuItem as DeprecatedPluginSidebarMoreMenuItem,
} from '@wordpress/edit-post';
import {
	store as editorStore,
	PluginDocumentSettingPanel,
	PluginSidebar,
	PluginSidebarMoreMenuItem,
} from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import { dispatch, useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import { Slot } from '@wordpress/components';

/**
 * Internal dependencies
 */
import CoursePricingPromoSidebar from './course-pricing-promo-sidebar';
import CourseAccessPeriodPromoSidebar from './course-access-period-promo-sidebar';
import CourseThemeSidebar from './course-theme/course-theme-sidebar';
import CourseVideoSidebar from './course-video-sidebar';
import CourseGeneralSidebar from './course-general-sidebar';
import SenseiIcon from '../../icons/logo-tree.svg';

if ( ! PluginDocumentSettingPanel ) {
	PluginDocumentSettingPanel = DeprecatedPluginDocumentSettingPanel;
}

if ( ! PluginSidebar ) {
	PluginSidebar = DeprecatedPluginSidebar;
}

if ( ! PluginSidebarMoreMenuItem ) {
	PluginSidebarMoreMenuItem = DeprecatedPluginSidebarMoreMenuItem;
}

export const pluginSidebarHandle = 'sensei-lms-course-settings-sidebar';
export const pluginDocumentHandle = 'sensei-lms-document-settings-sidebar';

export const CourseSidebar = () => {
	/**
	 * Filter to show or hide course pricing component.
	 *
	 * @since 4.9.0
	 *
	 * @hook  senseiCoursePricingHide This hook allows to pass a boolean value for hiding course pricing upsell.
	 * @return {boolean} 			  Hide the component.
	 */
	const hideCoursePricing = applyFilters( 'senseiCoursePricingHide', false );

	/**
	 * Filter to show or hide course expiration component.
	 *
	 * @since 4.9.0
	 *
	 * @hook  senseiCourseAccessPeriodHide This hook allows to pass a boolean value for hiding course expiration (access period) upsell.
	 * @return {boolean} 				   Hide the component.
	 */
	const hideAccessPeriod = applyFilters(
		'senseiCourseAccessPeriodHide',
		false
	);
	return (
		<>
			<PluginSidebarMoreMenuItem
				target={ pluginSidebarHandle }
				icon={ <SenseiIcon height="20" width="20" /> }
			>
				{ __( 'Course Settings', 'sensei-lms' ) }
			</PluginSidebarMoreMenuItem>
			<PluginSidebar
				name={ pluginSidebarHandle }
				title={ __( 'Course Settings', 'sensei-lms' ) }
				icon={ <SenseiIcon height="20" width="20" /> }
			>
				{ ! hideCoursePricing && <CoursePricingPromoSidebar /> }
				{ ! hideAccessPeriod && <CourseAccessPeriodPromoSidebar /> }
				<Slot name="SenseiCourseSidebar" />
				<CourseThemeSidebar />
				<CourseVideoSidebar />
				<CourseGeneralSidebar />
			</PluginSidebar>
		</>
	);
};

export const SenseiSettingsDocumentSidebar = () => {
	const isSenseiEditorPanelOpen = useSelect( ( select ) => {
		const isEditorPanelOpened = select( editorStore ).isEditorPanelOpened
			? select( editorStore ).isEditorPanelOpened
			: select( editPostStore ).isEditorPanelOpened;

		return isEditorPanelOpened(
			`${ pluginDocumentHandle }/${ pluginDocumentHandle }`
		);
	} );
	useEffect( () => {
		if ( ! isSenseiEditorPanelOpen ) {
			return;
		}

		const toggleEditorPanelOpened = dispatch( editorStore )
			.toggleEditorPanelOpened
			? dispatch( editorStore ).toggleEditorPanelOpened
			: dispatch( editPostStore ).toggleEditorPanelOpened;

		// when 'Course Settings' is clicked, isSenseiEditorPanelOpen returns true, so we open the 'Course Settings'
		// plugin sidebar and then close the 'Sensei Settings' panel which sets isSenseiEditorPanelOpen back to false.
		dispatch( editPostStore ).openGeneralSidebar(
			`${ pluginSidebarHandle }/${ pluginSidebarHandle }`
		);
		toggleEditorPanelOpened(
			`${ pluginDocumentHandle }/${ pluginDocumentHandle }`
		);
	}, [ isSenseiEditorPanelOpen ] );

	return (
		<PluginDocumentSettingPanel
			name={ pluginDocumentHandle }
			title={ __( 'Course Settings', 'sensei-lms' ) }
			className="sensei-plugin-document-setting-panel"
		></PluginDocumentSettingPanel>
	);
};
