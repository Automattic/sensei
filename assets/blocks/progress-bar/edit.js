import { ColorSettings } from '../../shared/blocks/settings';
import { __ } from '@wordpress/i18n';
import { mapValues } from 'lodash';

/**
 * Edit course progress bar component.
 *
 * @param {Object} props               Component properties.
 * @param {string} props.className     Custom class name.
 * @param {Object} props.attributes    The attributes of the component.
 * @param {Object} props.setAttributes Sets the attributes.
 */
export const EditProgressBarBlock = ( {
	className,
	attributes,
	setAttributes,
} ) => {
	const { textColor, barColor, barBackgroundColor } = attributes;
	const barStyle = {
		'--bar-color': barColor ? barColor : '#0064B4',
		'--bar-background-color': barBackgroundColor
			? barBackgroundColor
			: '#E6E6E6',
	};

	const colorSettings = {
		barColor: {
			label: __( 'Progress bar color', 'sensei-lms' ),
		},
		barBackgroundColor: {
			label: __( 'Progress bar background color', 'sensei-lms' ),
		},
		textColor: {
			label: __( 'Text color', 'sensei-lms' ),
		},
	};

	let colorProps = mapValues( attributes, ( colorValue ) => ( {
		color: colorValue,
	} ) );

	colorProps = {
		...colorProps,
		setBarColor: ( color ) => setAttributes( { barColor: color } ),
		setBarBackgroundColor: ( color ) =>
			setAttributes( { barBackgroundColor: color } ),
		setTextColor: ( color ) => setAttributes( { textColor: color } ),
	};

	return (
		<>
			<div className={ className }>
				<section
					className="wp-block-sensei-lms-progress-heading"
					style={ { color: textColor } }
				>
					<div className="wp-block-sensei-lms-progress-heading__lessons">
						5 Lessons
					</div>
					<div className="wp-block-sensei-lms-progress-heading__completed">
						3 completed (60%)
					</div>
				</section>
				<progress
					style={ barStyle }
					className="wp-block-sensei-lms-progress-bar"
					max="100"
					value="50"
				/>
			</div>
			<ColorSettings
				colorSettings={ colorSettings }
				props={ colorProps }
			/>
		</>
	);
};
