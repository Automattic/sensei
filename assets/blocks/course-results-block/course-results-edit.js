/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { compose } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import CourseResultsSettings from './course-results-settings';
import {
	withColorSettings,
	withDefaultColor,
	withDefaultBlockStyle,
} from '../../shared/blocks/settings';
import { dispatch } from '@wordpress/data';
import { sprintf, __ } from '@wordpress/i18n';

/**
 * Sample lesson component.
 *
 * @param {Object} props              Component props.
 * @param {Array}  props.lessonNumber The lesson number to use in the sample title.
 */
const SampleLesson = ( { lessonNumber } ) => (
	<div className="wp-block-sensei-lms-course-results__lesson">
		<span className="wp-block-sensei-lms-course-results__lesson__title">
			{ sprintf(
				/* translators: Mock lesson number. */
				__( 'Lesson %s', 'sensei-lms' ),
				lessonNumber
			) }
		</span>
		<span className="wp-block-sensei-lms-course-results__lesson__score">
			xx%
		</span>
	</div>
);

/**
 * Sample module component.
 *
 * @param {Object}  props               Component props.
 * @param {string}  props.moduleName    The name of the module.
 * @param {Array}   props.lessonNumbers The lesson numbers to include in the sample module.
 * @param {string}  props.style         The style selected for the results block.
 * @param {boolean} props.moduleBorder  If modules have borders.
 */
const SampleModule = ( { moduleName, lessonNumbers, style, moduleBorder } ) => (
	<section
		className={ classnames( 'wp-block-sensei-lms-course-results__module', {
			'wp-block-sensei-lms-course-results__module__bordered': moduleBorder,
		} ) }
	>
		<header className="wp-block-sensei-lms-course-results__module__header">
			<h2 className="wp-block-sensei-lms-course-results__module__title">
				{ moduleName }
			</h2>
		</header>

		{ 'minimal' === style && (
			<div className="wp-block-sensei-lms-course-results__module__separator" />
		) }

		{ lessonNumbers.map( ( lessonNumber, index ) => (
			<SampleLesson key={ index } lessonNumber={ lessonNumber } />
		) ) }
	</section>
);

/**
 * Edit course results block component.
 *
 * @param {Object}  props                         Component props.
 * @param {string}  props.className               Custom class name.
 * @param {Object}  props.defaultMainColor        Default main color.
 * @param {Object}  props.defaultTextColor        Default text color.
 * @param {Object}  props.defaultBorderColor      Default border color.
 * @param {Object}  props.mainColor               Header main color.
 * @param {Object}  props.textColor               Header text color.
 * @param {Object}  props.borderColor             Default border color.
 * @param {Object}  props.attributes              Block attributes.
 * @param {boolean} props.attributes.moduleBorder True if a modules should have a border.
 */
const CourseResultsEdit = ( props ) => {
	const {
		className,
		defaultMainColor,
		defaultTextColor,
		defaultBorderColor,
		mainColor,
		textColor,
		borderColor,
		attributes: { moduleBorder },
	} = props;

	const styleRegex = /is-style-(\w+)/;
	const style = className.match( styleRegex )?.[ 1 ];

	// Header styles.
	const headerStyles = {
		default: {
			background: mainColor?.color || defaultMainColor?.color,
			color: textColor?.color || defaultTextColor?.color,
		},
		minimal: {
			color: textColor?.color,
		},
	}[ style ];

	const styleVars = {
		'--sensei-module-header-bg-color':
			headerStyles?.background || 'inherit',
		'--sensei-module-header-text-color': headerStyles?.color || 'inherit',
		'--sensei-module-header-separator-color': mainColor?.color || 'inherit',
		'--sensei-module-border-color':
			borderColor?.color || defaultBorderColor?.color,
	};

	return (
		<>
			<CourseResultsSettings { ...props } />
			<section className={ className } style={ styleVars }>
				<div className="wp-block-sensei-lms-course-results__gradeWrapper">
					<div className="wp-block-sensei-lms-course-results__grade">
						<div className="wp-block-sensei-lms-course-results__grade__preface">
							{ __( 'Your Total Grade', 'sensei-lms' ) }
						</div>
						<div className="wp-block-sensei-lms-course-results__grade__score">
							xx%
						</div>
					</div>
				</div>
				<SampleModule
					moduleName={ __( 'Module A', 'sensei-lms' ) }
					lessonNumbers={ [ 1, 2, 3 ] }
					moduleBorder={ moduleBorder }
					style={ style }
				/>
				<SampleModule
					moduleName={ __( 'Module B', 'sensei-lms' ) }
					lessonNumbers={ [ 4, 5, 6 ] }
					moduleBorder={ moduleBorder }
					style={ style }
				/>
				<SampleModule
					moduleName={ __( 'Module C', 'sensei-lms' ) }
					lessonNumbers={ [ 7, 8 ] }
					moduleBorder={ moduleBorder }
					style={ style }
				/>
			</section>
		</>
	);
};

export default compose(
	withDefaultBlockStyle(),
	withColorSettings( {
		mainColor: {
			style: 'background-color',
			label: __( 'Module color', 'sensei-lms' ),
		},
		textColor: {
			style: 'color',
			label: __( 'Module text color', 'sensei-lms' ),
		},
		borderColor: {
			style: 'border-color',
			label: __( 'Module border color', 'sensei-lms' ),
			onChange: ( { clientId, colorValue } ) =>
				dispatch( 'core/block-editor' ).updateBlockAttributes(
					clientId,
					{ borderColorValue: colorValue }
				),
		},
	} ),
	withDefaultColor( {
		defaultMainColor: {
			style: 'background-color',
			probeKey: 'primaryColor',
		},
		defaultTextColor: {
			style: 'color',
			probeKey: 'primaryContrastColor',
		},
		defaultBorderColor: {
			style: 'border-color',
			probeKey: 'primaryColor',
		},
	} )
)( CourseResultsEdit );
