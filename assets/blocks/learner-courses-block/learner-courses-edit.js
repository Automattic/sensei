/**
 * External dependencies
 */
import classnames from 'classnames';
import { omitBy } from 'lodash';

/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';
import { Icon, image } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import CourseProgress from '../../shared/blocks/course-progress';
import LearnerCoursesSettings from './learner-courses-settings';

/**
 * Learner Settings component.
 *
 * @param {Object}   props
 * @param {Object}   props.className          Block className.
 * @param {Object}   props.attributes         Block attributes.
 * @param {Object}   props.attributes.options Block options attribute.
 * @param {Function} props.setAttributes      Block set attributes function.
 */
const LearnerCoursesEdit = ( {
	className,
	attributes: { options },
	setAttributes,
} ) => {
	const [ filter, setFilter ] = useState( 'all' );

	const filterHandler = ( filterValue ) => ( e ) => {
		e.preventDefault();

		setFilter( filterValue );
	};

	const filters = [
		{
			label: __( 'All Courses', 'sensei-lms' ),
			value: 'all',
		},
		{
			label: __( 'Active Courses', 'sensei-lms' ),
			value: 'active',
		},
		{
			label: __( 'Completed Courses', 'sensei-lms' ),
			value: 'completed',
		},
	];

	// Set options function used for block settings.
	const setOptions = ( editedOptions ) =>
		setAttributes( { options: { ...options, ...editedOptions } } );

	// Courses placeholder map function.
	const coursesPlaceholderMap = ( i, index, array ) => {
		const completed =
			// All items should be in progress if active filter is selected.
			filter !== 'active' &&
			//  Show last one as completed.
			( index === array.length - 1 ||
				//  Show all as completed if completed is filtered.
				filter === 'completed' );

		return (
			<li
				className="wp-block-sensei-lms-learner-courses__courses-list__item"
				key={ index }
			>
				{ options.featuredImageEnabled && (
					<div
						className="wp-block-sensei-lms-learner-courses__courses-list__featured-image"
						role="img"
						aria-label="Featured image"
					>
						<Icon icon={ image } size={ 48 } />
					</div>
				) }
				<div className="wp-block-sensei-lms-learner-courses__courses-list__course-info">
					{ options.courseCategoryEnabled && (
						<small className="wp-block-sensei-lms-learner-courses__courses-list__category">
							{ __( 'Category name', 'sensei-lms' ) }
						</small>
					) }
					<header className="wp-block-sensei-lms-learner-courses__courses-list__header">
						<h3 className="wp-block-sensei-lms-learner-courses__courses-list__title">
							{ __( 'Course Title', 'sensei-lms' ) }
						</h3>
						{ completed && (
							<em className="wp-block-sensei-lms-learner-courses__courses-list__badge">
								{ __( 'Completed', 'sensei-lms' ) }
							</em>
						) }
					</header>
					{ options.courseDescriptionEnabled && (
						<p className="wp-block-sensei-lms-learner-courses__courses-list__description">
							{ __(
								'This is a preview of the course description…',
								'sensei-lms'
							) }
						</p>
					) }

					{ options.progressBarEnabled && (
						<CourseProgress
							lessonsCount={ 3 }
							completedCount={ completed ? 3 : 1 }
							hidePercentage
						/>
					) }
				</div>
			</li>
		);
	};

	return (
		<>
			<section
				className={ className }
				style={ omitBy(
					{
						'--progress-bar-height': `${ options.progressBarHeight }px`,
						'--progress-bar-border-radius': `${ options.progressBarBorderRadius }px`,
						'--primary-color': options.primaryColor,
						'--accent-color': options.accentColor,
					},
					// Exclude not set values.
					( value ) => {
						return [ undefined, null, 'undefinedpx' ].includes(
							value
						);
					}
				) }
			>
				<ul className="wp-block-sensei-lms-learner-courses__filter">
					{ filters.map( ( { label, value } ) => (
						<li
							key={ value }
							className={ classnames(
								'wp-block-sensei-lms-learner-courses__filter__item',
								{
									'--is-active': value === filter,
								}
							) }
						>
							<a
								className="wp-block-sensei-lms-learner-courses__filter__link"
								href={ `#${ value }` }
								onClick={ filterHandler( value ) }
							>
								{ label }
							</a>
						</li>
					) ) }
				</ul>
				<ul
					className={ classnames(
						'wp-block-sensei-lms-learner-courses__courses-list',
						`--is-${ options.layoutView }-view`,
						`--is-${ options.columns }-columns`
					) }
				>
					{ Array.from( { length: options.columns } ).map(
						coursesPlaceholderMap
					) }
				</ul>
			</section>
			<LearnerCoursesSettings
				options={ options }
				setOptions={ setOptions }
			/>
		</>
	);
};

export default LearnerCoursesEdit;
