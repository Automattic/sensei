/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { __, _n } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import NumberControl from '../editor-components/number-control';
import { DIFFICULTIES } from './constants';

const LessonPropertiesEdit = ( props ) => {
	const {
		className,
		attributes: { difficulty, length },
		setAttributes,
	} = props;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Properties', 'sensei-lms' ) }>
					<NumberControl
						id="sensei-lesson-length"
						label={ __( 'Length', 'sensei-lms' ) }
						min={ 0 }
						step={ 1 }
						value={ length }
						onChange={ ( newLength ) =>
							setAttributes( { length: newLength } )
						}
						suffix={ _n(
							'minute',
							'minutes',
							length,
							'sensei-lms'
						) }
					/>

					<SelectControl
						label={ __( 'Difficulty', 'sensei-lms' ) }
						options={ DIFFICULTIES.map( ( { label, value } ) => ( {
							label,
							value,
						} ) ) }
						value={ difficulty }
						onChange={ ( newDifficulty ) =>
							setAttributes( { difficulty: newDifficulty } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div className={ className }>
				<span
					className={ classnames(
						'wp-block-sensei-lms-lesson-properties__length',
						{ disabled: ! length }
					) }
				>
					{ __( 'Length', 'sensei-lms' ) +
						': ' +
						length +
						' ' +
						_n( 'minute', 'minutes', length, 'sensei-lms' ) }
				</span>

				<span
					className={ classnames(
						'wp-block-sensei-lms-lesson-properties__separator',
						{ disabled: ! length || ! difficulty }
					) }
				>
					|
				</span>

				<span
					className={ classnames(
						'wp-block-sensei-lms-lesson-properties__difficulty',
						{ disabled: ! difficulty }
					) }
				>
					{ __( 'Difficulty', 'sensei-lms' ) +
						': ' +
						DIFFICULTIES.find(
							( lessonDifficulty ) =>
								difficulty === lessonDifficulty.value
						)?.label }
				</span>
			</div>
		</>
	);
};

export default LessonPropertiesEdit;
