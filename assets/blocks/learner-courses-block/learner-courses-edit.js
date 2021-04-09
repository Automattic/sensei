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
 * Featured image placeholder element.
 */
const FeaturedImagePlaceholder = () => (
	<div
		className="wp-block-sensei-lms-learner-courses__courses-list__featured-image"
		role="img"
		aria-label="Featured image"
	>
		<Icon icon={ image } size={ 48 } />
	</div>
);

/**
 * Wrapper for CSS variables & related classes.
 *
 * @param {Object} props
 * @param {string} props.tag       HTML tag.
 * @param {Array}  props.variables CSS variables.
 * @param {Array}  props.children  Children elements.
 * @param {*}      props.className Classes.
 */
const StylesWrapper = ( { tag, variables, children, className } ) => {
	const isEmpty = ( value ) => {
		return [ undefined, null, 'undefinedpx' ].includes( value );
	};
	const Tag = tag || 'div';
	return (
		<Tag
			className={ classnames( className, {
				'has-sensei-primary-color': !! variables.primaryColor,
				'has-sensei-accent-color': !! variables.accentColor,
			} ) }
			style={ omitBy(
				{
					'--sensei-progress-bar-height': variables.progressBarHeight,
					'--sensei-progress-bar-border-radius':
						variables.progressBarBorderRadius,
					'--sensei-primary-color': variables.primaryColor,
					'--sensei-accent-color': variables.accentColor,
				},
				isEmpty
			) }
		>
			{ children }
		</Tag>
	);
};

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
				{ options.courseCategoryEnabled && (
					<small className="wp-block-sensei-lms-learner-courses__courses-list__category">
						{ __( 'Category name', 'sensei-lms' ) }
					</small>
				) }
				<h3 className="wp-block-sensei-lms-learner-courses__courses-list__title">
					{ /* eslint-disable-next-line jsx-a11y/anchor-is-valid */ }
					<a href="#">{ __( 'Course Title', 'sensei-lms' ) }</a>
				</h3>
				{ options.featuredImageEnabled && <FeaturedImagePlaceholder /> }

				{ completed && (
					<div>
						<em className="wp-block-sensei-lms-learner-courses__courses-list__badge">
							{ __( 'Completed', 'sensei-lms' ) }
						</em>
					</div>
				) }

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
				{ completed && (
					<div className="sensei-results-links wp-block-buttons is-content-justification-right">
						<div className="wp-block-button">
							{ /* eslint-disable-next-line jsx-a11y/anchor-is-valid */ }
							<a className="wp-block-button__link" href="#">
								{ __( 'View Results', 'sensei-lms' ) }
							</a>
						</div>
					</div>
				) }
			</li>
		);
	};

	return (
		<>
			<StylesWrapper
				className={ className }
				variables={ {
					primaryColor: options.primaryColor,
					accentColor: options.accentColor,
					progressBarHeight: `${ options.progressBarHeight }px`,
					progressBarBorderRadius: `${ options.progressBarBorderRadius }px`,
				} }
			>
				<p className="wp-block-sensei-lms-learner-courses__filter">
					{ filters.map( ( { label, value } ) => (
						<a
							key={ value }
							href={ `#${ value }` }
							onClick={ filterHandler( value ) }
							className={ classnames(
								'wp-block-sensei-lms-learner-courses__filter__item',
								{
									active: value === filter,
								}
							) }
						>
							{ label }
						</a>
					) ) }
				</p>
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
			</StylesWrapper>
			<LearnerCoursesSettings
				options={ options }
				setOptions={ setOptions }
			/>
		</>
	);
};

export default LearnerCoursesEdit;
