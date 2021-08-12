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
	<li className="wp-block-sensei-lms-course-results__lesson">
		{ /* eslint-disable-next-line jsx-a11y/anchor-is-valid */ }
		<a href="#" className="wp-block-sensei-lms-course-results__lesson-link">
			<span className="wp-block-sensei-lms-course-results__lesson-title">
				{ sprintf(
					/* translators: Mock lesson number. */
					__( 'Lesson %s', 'sensei-lms' ),
					lessonNumber
				) }
			</span>
			<span className="wp-block-sensei-lms-course-results__lesson-score">
				xx%
			</span>
		</a>
	</li>
);

/**
 * Sample module component.
 *
 * @param {Object}  props              Component props.
 * @param {string}  props.moduleName   The name of the module.
 * @param {boolean} props.moduleBorder If modules have borders.
 * @param {string}  props.headerStyles The module header styles.
 * @param {string}  props.style        The style selected for the results block.
 */
const SampleModule = ( { moduleName, moduleBorder, headerStyles, style } ) => (
	<section
		className={ classnames( 'wp-block-sensei-lms-course-results__module', {
			'wp-block-sensei-lms-course-results__module--has-border': moduleBorder,
		} ) }
	>
		<header
			className="wp-block-sensei-lms-course-results__module-header"
			style={ headerStyles }
		>
			<h3 className="wp-block-sensei-lms-course-results__module-title">
				{ moduleName }
			</h3>
		</header>

		{ 'minimal' === style && (
			<div className="wp-block-sensei-lms-course-results__separator" />
		) }

		<ul className="wp-block-sensei-lms-course-results__lessons">
			{ [ 1, 2 ].map( ( lessonNumber, index ) => (
				<SampleLesson key={ index } lessonNumber={ lessonNumber } />
			) ) }
		</ul>
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
				<div className="wp-block-sensei-lms-course-results__grade">
					<span className="wp-block-sensei-lms-course-results__grade-label">
						{ __( 'Your Total Grade', 'sensei-lms' ) }
					</span>
					<span className="wp-block-sensei-lms-course-results__grade-score">
						XX%
					</span>
				</div>
				<h2 className="wp-block-sensei-lms-course-results__course-title">
					{ __( 'Course Title', 'sensei-lms' ) }
				</h2>
				<SampleModule
					moduleName={ __( 'Module A', 'sensei-lms' ) }
					moduleBorder={ moduleBorder }
					headerStyles={ headerStyles }
					style={ style }
				/>
				<SampleModule
					moduleName={ __( 'Module B', 'sensei-lms' ) }
					moduleBorder={ moduleBorder }
					headerStyles={ headerStyles }
					style={ style }
				/>
				<SampleModule
					moduleName={ __( 'Module C', 'sensei-lms' ) }
					moduleBorder={ moduleBorder }
					headerStyles={ headerStyles }
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
