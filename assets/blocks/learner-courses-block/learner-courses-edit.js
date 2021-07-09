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
 * @param {string} props.tagName   HTML tag.
 * @param {Array}  props.variables CSS variables.
 * @param {Object} props.children  Children elements.
 * @param {string} props.className Classes.
 */
const StylesWrapper = ( {
	tagName: TagName = 'div',
	variables,
	children,
	className,
	...props
} ) => {
	const isEmpty = ( value ) => {
		return [ undefined, null, 'undefinedpx' ].includes( value );
	};
	return (
		<TagName
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
			{ ...props }
		>
			{ children }
		</TagName>
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
				className="wp-block-sensei-lms-learner-courses__courses-list__item course"
				key={ index }
			>
				<section className="entry">
					{ options.featuredImageEnabled && (
						<FeaturedImagePlaceholder />
					) }

					<div className="wp-block-sensei-lms-learner-courses__courses-list__details">
						<h3 className="wp-block-sensei-lms-learner-courses__courses-list__title">
							{ /* eslint-disable-next-line jsx-a11y/anchor-is-valid */ }
							<a href="#">
								{ __( 'Course Title', 'sensei-lms' ) }
							</a>
						</h3>

						{ options.courseCategoryEnabled && (
							<span className="wp-block-sensei-lms-learner-courses__courses-list__category">
								{ __( 'Category Name', 'sensei-lms' ) }
							</span>
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
								wrapperAttributes={ {
									className:
										'wp-block-sensei-lms-course-progress',
								} }
								hidePercentage
							/>
						) }

						{ completed && (
							<div className="sensei-results-links wp-block-buttons">
								<div className="wp-block-button">
									{ /* eslint-disable-next-line jsx-a11y/anchor-is-valid */ }
									<a
										className="wp-block-button__link"
										href="#"
									>
										{ __( 'View Results', 'sensei-lms' ) }
									</a>
								</div>
							</div>
						) }
					</div>
				</section>
			</li>
		);
	};

	return (
		<>
			<StylesWrapper
				tagName="section"
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
						`wp-block-sensei-lms-learner-courses__courses-list--is-${ options.layoutView }-view`
					) }
				>
					{ Array.from( { length: 2 } ).map( coursesPlaceholderMap ) }
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
