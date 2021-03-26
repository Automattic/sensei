/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import CourseProgress from './course-progress';
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
			label: __( 'All courses', 'sensei-lms' ),
			value: 'all',
		},
		{
			label: __( 'Active courses', 'sensei-lms' ),
			value: 'active',
		},
		{
			label: __( 'Completed courses', 'sensei-lms' ),
			value: 'completed',
		},
	];

	const setOptions = ( editedOptions ) =>
		setAttributes( { options: { ...options, ...editedOptions } } );

	return (
		<>
			<section className={ className }>
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
						( i, index, array ) => {
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
										<div className="wp-block-sensei-lms-learner-courses__courses-list__featured-image" />
									) }
									<div>
										{ options.courseCategoryEnabled && (
											<small className="wp-block-sensei-lms-learner-courses__courses-list__category">
												Category name
											</small>
										) }
										<header className="wp-block-sensei-lms-learner-courses__courses-list__header">
											<h3 className="wp-block-sensei-lms-learner-courses__courses-list__title">
												{ __(
													'Course title goes here',
													'sensei-lms'
												) }
											</h3>
											{ completed && (
												<em className="wp-block-sensei-lms-learner-courses__courses-list__badge">
													{ __(
														'Completed',
														'sensei-lms'
													) }
												</em>
											) }
										</header>
										{ options.courseDescriptionEnabled && (
											<p className="wp-block-sensei-lms-learner-courses__courses-list__description">
												{ __(
													'Here is a short course description. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas arcu turpis mauris…',
													'sensei-lms'
												) }
											</p>
										) }
										<CourseProgress
											lessons={ 3 }
											completed={ completed ? 3 : 1 }
											hideProgressBar={
												! options.progressBarEnabled
											}
										/>
									</div>
								</li>
							);
						}
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
