/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';
import { __, sprintf, _n } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
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
				<ul className="wp-block-sensei-lms-learner-courses__courses-list">
					{ Array.from( { length: 2 } ).map( ( i, index ) => (
						<li
							className="wp-block-sensei-lms-learner-courses__courses-list__item"
							key={ index }
						>
							<h3 className="wp-block-sensei-lms-learner-courses__courses-list__title">
								{ __( 'Course title goes here', 'sensei-lms' ) }
							</h3>
							<p className="wp-block-sensei-lms-learner-courses__courses-list__description">
								{ __(
									'Here is a short two line course description. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas arcu turpis mauris…',
									'sensei-lms'
								) }
							</p>
							<div className="wp-block-sensei-lms-learner-courses__course-progress">
								<div
									className="wp-block-sensei-lms-learner-courses__progress-bar"
									role="progressbar"
									aria-valuenow={ 33 }
									aria-valuemin="0"
									aria-valuemax="100"
								>
									<div
										className="wp-block-sensei-lms-learner-courses__progress-bar__fill"
										style={ { width: '33%' } }
									></div>
								</div>
								<div className="wp-block-sensei-lms-learner-courses__course-progress__numbers">
									<strong className="wp-block-sensei-lms-learner-courses__course-progress__number-lessons">
										{ sprintf(
											// translators: placeholder is number of lessons in the course.
											_n(
												'%d Lesson',
												'%d Lessons',
												3,
												'sensei-lms'
											),
											3
										) }
									</strong>
									<em className="wp-block-sensei-lms-learner-courses__course-progress__completed-lessons">
										{ sprintf(
											// translators: placeholder is number of completed lessons in the course.
											_n(
												'%d Completed',
												'%d Completed',
												1,
												'sensei-lms'
											),
											1
										) }
									</em>
								</div>
							</div>
						</li>
					) ) }
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
