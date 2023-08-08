/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect, dispatch, select } from '@wordpress/data';
import {
	PanelBody,
	CheckboxControl,
	SelectControl,
	HorizontalRule,
	ExternalLink,
} from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import apiFetch from '@wordpress/api-fetch';
import { useState, useEffect, useMemo } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import editorLifecycle from '../../shared/helpers/editor-lifecycle';
import {
	extractStructure,
	getFirstBlockByName,
} from '../../blocks/course-outline/data';

const CourseGeneralSidebar = () => {
	const course = useSelect( ( select ) => {
		return select( 'core/editor' ).getCurrentPost();
	} );
	const [ author, setAuthor ] = useState(
		window.sensei.courseSettingsSidebar.author
	);

	let courses = window.sensei.courseSettingsSidebar.courses;
	if ( courses && courses.length ) {
		courses = courses.map( ( course ) => {
			return { label: course.post_title, value: course.ID };
		} );
		courses.push( { label: __( 'None', 'sensei-lms' ), value: 0 } );
	}

	let teachers = window.sensei.courseSettingsSidebar.teachers;
	if ( teachers && teachers.length ) {
		teachers = teachers.map( ( usr ) => {
			return { label: usr.display_name, value: usr.ID };
		} );
	}

	const [ meta, setMeta ] = useEntityProp( 'postType', 'course', 'meta' );
	const featured = meta._course_featured;
	const prerequisite = meta._course_prerequisite;
	const notification = meta.disable_notification;
	const openAccess = meta._open_access;

	useEffect( () =>
		editorLifecycle( {
			onSaveStart: () => {
				if ( author !== course.author ) {
					const outlineBlock = getFirstBlockByName(
						'sensei-lms/course-outline',
						select( 'core/block-editor' ).getBlocks()
					);
					const moduleSlugs =
						outlineBlock &&
						extractStructure( outlineBlock.innerBlocks )
							.filter( ( block ) => block.slug )
							.map( ( block ) => block.slug );
					apiFetch( {
						path: '/sensei-internal/v1/course-utils/update-teacher',
						method: 'PUT',
						data: {
							[ window.sensei.courseSettingsSidebar.nonce_name ]:
								window.sensei.courseSettingsSidebar.nonce_value,
							post_id: course.id,
							teacher: author,
							custom_slugs: JSON.stringify( moduleSlugs ),
						},
					} ).catch( ( response ) => {
						if ( response.message ) {
							dispatch( 'core/notices' ).createNotice(
								'warning',
								response.message,
								{
									isDismissible: true,
								}
							);
						}
					} );
				}
			},
		} )
	);

	/**
	 * Allows to show or hide the multiple teachers upgrade.
	 *
	 * @since 4.9.0
	 *
	 * @param {boolean} Whether the upgrade should be hidden or not. Default false. True will hide the upgrade.
	 */
	const hideCoteachersUpgrade = applyFilters(
		'senseiCourseSettingsMultipleTeachersUpgradeHide',
		false
	);

	/**
	 * Returns the component to render after the teacher course setting.
	 *
	 * @since 4.9.0
	 *
	 * @param {Function} The existing component hooked into the filter.
	 */
	const AfterTeachersSection = useMemo(
		() => applyFilters( 'senseiCourseSettingsTeachersAfter', () => null ),
		[]
	);

	return (
		<PanelBody title={ __( 'General', 'sensei-lms' ) } initialOpen={ true }>
			<h3>{ __( 'Teacher', 'sensei-lms' ) }</h3>
			{ teachers.length ? (
				<SelectControl
					value={ author }
					options={ teachers }
					onChange={ ( new_author ) => {
						new_author = parseInt( new_author );
						setAuthor( new_author );
						dispatch( 'core' ).editEntityRecord(
							'postType',
							'course',
							course.id,
							{ author: new_author }
						);
					} }
				/>
			) : null }

			{ ! hideCoteachersUpgrade && (
				<div className="sensei-course-coteachers-wrapper">
					{ __( 'Multiple teachers?', 'sensei-lms' ) }{ ' ' }
					<ExternalLink href="https://senseilms.com/sensei-pro/?utm_source=plugin_sensei&utm_medium=upsell&utm_campaign=co-teachers">
						{ __( 'Upgrade to Sensei Pro', 'sensei-lms' ) }
					</ExternalLink>
				</div>
			) }

			<AfterTeachersSection
				courseAuthorId={ author }
				courseId={ course.id }
			/>

			<HorizontalRule />

			<h3>{ __( 'Course Prerequisite', 'sensei-lms' ) }</h3>
			{ ! courses.length ? (
				<p>
					{ __(
						'No courses exist yet. Please add some first.',
						'sensei-lms'
					) }
				</p>
			) : null }
			{ courses.length ? (
				<SelectControl
					value={ prerequisite }
					options={ courses }
					onChange={ ( value ) =>
						setMeta( { ...meta, _course_prerequisite: value } )
					}
				/>
			) : null }

			{ window.sensei.courseSettingsSidebar.features?.open_access && (
				<>
					<HorizontalRule />

					<h3>{ __( 'Access', 'sensei-lms' ) }</h3>
					<CheckboxControl
						label={ __( 'Open Access', 'sensei-lms' ) }
						checked={ openAccess }
						onChange={ ( checked ) =>
							setMeta( { ...meta, _open_access: checked } )
						}
						help={ __(
							'Visitors can take this course without signing up. Not available for paid courses.',
							'sensei-lms'
						) }
					/>
				</>
			) }

			<HorizontalRule />

			<h3>{ __( 'Featured Course', 'sensei-lms' ) }</h3>
			<CheckboxControl
				label={ __( 'Feature this course.', 'sensei-lms' ) }
				checked={ featured == 'featured' }
				onChange={ ( checked ) =>
					setMeta( {
						...meta,
						_course_featured: checked ? 'featured' : '',
					} )
				}
			/>

			<HorizontalRule />

			<h3>{ __( 'Course Notifications', 'sensei-lms' ) }</h3>
			<CheckboxControl
				label={ __(
					'Disable notifications on this course?',
					'sensei-lms'
				) }
				checked={ notification }
				onChange={ ( checked ) =>
					setMeta( { ...meta, disable_notification: checked } )
				}
			/>

			<HorizontalRule />

			<h3>{ __( 'Course Management', 'sensei-lms' ) }</h3>
			<p>
				<a
					href={ `/wp-admin/admin.php?page=sensei_learners&course_id=${ course.id }&view=learners` }
				>
					{ __( 'Manage Students', 'sensei-lms' ) }
				</a>
			</p>
			<p>
				<a
					href={ `/wp-admin/admin.php?page=sensei_grading&course_id=${ course.id }&view=learners` }
				>
					{ __( 'Manage Grading', 'sensei-lms' ) }
				</a>
			</p>
		</PanelBody>
	);
};

export default CourseGeneralSidebar;
