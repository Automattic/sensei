/**
 * WordPress dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { __, _n } from '@wordpress/i18n';

/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import NumberControl from '../editor-components/number-control';
import ToggleLegacyLessonMetaboxesWrapper from '../toggle-legacy-lesson-metaboxes-wrapper';
import { DIFFICULTIES } from './constants';

const LessonMetadataEdit = ( props ) => {
	const {
		className,
		attributes: { difficulty, length },
		setAttributes,
	} = props;

	return (
		<ToggleLegacyLessonMetaboxesWrapper { ...props }>
			<InspectorControls>
				<PanelBody title={ __( 'Metadata', 'sensei-lms' ) }>
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

			<div className={ classnames( 'lesson-metadata', className ) }>
				{ !! length && (
					<span className="lesson-length">
						{ __( 'Length', 'sensei-lms' ) +
							': ' +
							length +
							' ' +
							_n( 'minute', 'minutes', length, 'sensei-lms' ) }
					</span>
				) }

				{ !! length && difficulty && (
					<span className="separator">|</span>
				) }

				{ difficulty && (
					<span className="lesson-difficulty">
						{ __( 'Difficulty', 'sensei-lms' ) +
							': ' +
							DIFFICULTIES.find(
								( lessonDifficulty ) =>
									difficulty === lessonDifficulty.value
							)?.label }
					</span>
				) }
			</div>
		</ToggleLegacyLessonMetaboxesWrapper>
	);
};

export default LessonMetadataEdit;
