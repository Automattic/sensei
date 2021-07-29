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
import { useCallback } from '@wordpress/element';
import { dispatch } from '@wordpress/data';

/**
 * External dependencies
 */
import classnames from 'classnames';
import { sprintf, __ } from '@wordpress/i18n';

/**
 * Edit course results block component.
 *
 * @param {Object}   props                         Component props.
 * @param {string}   props.clientId                Block client ID.
 * @param {string}   props.className               Custom class name.
 * @param {Object}   props.mainColor               Header main color.
 * @param {Object}   props.textColor               Header text color.
 * @param {Object}   props.borderColor             Default border color.
 * @param {Object}   props.defaultMainColor        Default main color.
 * @param {Object}   props.defaultTextColor        Default text color.
 * @param {Object}   props.defaultBorderColor      Default border color.
 * @param {Object}   props.attributes              Block attributes.
 * @param {boolean}  props.attributes.moduleBorder True if a modules should have a border.
 * @param {Function} props.setAttributes           Block setAttributes callback.
 */
const CourseResultsEdit = ( props ) => {
	const {
		className,
		defaultMainColor,
		defaultTextColor,
		defaultBorderColor,
		textColor,
		mainColor,
		borderColor,
		attributes: { moduleBorder },
	} = props;

	const styleRegex = /is-style-(\w+)/;
	const style = className.match( styleRegex )?.[ 1 ];

	// Minimal border element.
	let minimalBorder;
	if ( 'minimal' === style ) {
		minimalBorder = (
			<div className="wp-block-sensei-lms-course-results__module__separator" />
		);
	}

	const renderSampleLesson = useCallback( ( lessonNumber ) => {
		const lessonName = sprintf(
			/* translators: Mock lesson number. */
			__( 'Lesson %s', 'sensei-lms' ),
			lessonNumber
		);

		return (
			<div className="wp-block-sensei-lms-course-results__lesson">
				<span className="wp-block-sensei-lms-course-results__lesson__title">
					{ lessonName }
				</span>
				<span className="wp-block-sensei-lms-course-results__lesson__score">
					xx%
				</span>
			</div>
		);
	}, [] );

	const renderSampleModule = useCallback(
		( moduleName, lessonNumbers ) => (
			<section
				className={ classnames(
					'wp-block-sensei-lms-course-results__module',
					{
						'wp-block-sensei-lms-course-results__module__bordered': moduleBorder,
					}
				) }
			>
				<header className="wp-block-sensei-lms-course-results__module__header">
					<h2 className="wp-block-sensei-lms-course-results__module__title">
						{ moduleName }
					</h2>
				</header>
				{ minimalBorder }

				{ lessonNumbers.map( ( lessonNumber ) =>
					renderSampleLesson( lessonNumber )
				) }
			</section>
		),
		[ moduleBorder, minimalBorder, renderSampleLesson ]
	);

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
				{ renderSampleModule( __( 'Module A', 'sensei-lms' ), [
					1,
					2,
					3,
				] ) }
				{ renderSampleModule( __( 'Module B', 'sensei-lms' ), [
					4,
					5,
				] ) }
				{ renderSampleModule( __( 'Module C', 'sensei-lms' ), [
					6,
					7,
				] ) }
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
