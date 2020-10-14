import { withColorSettings } from '../../shared/blocks/settings';
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';

/**
 * Edit course progress bar component.
 *
 * @param {Object} props                    Component properties.
 * @param {string} props.className          Custom class name.
 * @param {Object} props.barColor           Color object for the progress bar.
 * @param {Object} props.barBackgroundColor Color object for the background of the progress bar.
 * @param {Object} props.textColor          Color object for the text.
 */
export const EditProgressBarBlock = ( {
	className,
	barColor,
	barBackgroundColor,
	textColor,
} ) => {
	const progress = '60%';

	const wrapperAttributes = {
		className: classnames( className, textColor?.class ),
		style: {
			color: textColor?.color,
		},
	};
	const barAttributes = {
		className: barColor?.class,
		style: {
			backgroundColor: barColor?.color,
			width: progress,
		},
	};
	const barBackgroundAttributes = {
		className: classnames(
			'wp-block-sensei-lms-progress-bar',
			barBackgroundColor?.class
		),
		style: {
			backgroundColor: barBackgroundColor?.color,
		},
	};

	return (
		<>
			<div { ...wrapperAttributes }>
				<section className="wp-block-sensei-lms-progress-heading">
					<div className="wp-block-sensei-lms-progress-heading__lessons">
						5 Lessons
					</div>
					<div className="wp-block-sensei-lms-progress-heading__completed">
						3 completed ({ progress })
					</div>
				</section>
				<div { ...barBackgroundAttributes }>
					<div { ...barAttributes } />
				</div>
			</div>
		</>
	);
};

export default withColorSettings( {
	barColor: {
		style: 'background-color',
		label: __( 'Progress bar color', 'sensei-lms' ),
	},
	barBackgroundColor: {
		style: 'background-color',
		label: __( 'Progress bar background color', 'sensei-lms' ),
	},
	textColor: {
		style: 'color',
		label: __( 'Text color', 'sensei-lms' ),
	},
} )( EditProgressBarBlock );
