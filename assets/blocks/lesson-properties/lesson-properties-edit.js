/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { useCallback } from '@wordpress/element';
import { useEntityProp } from '@wordpress/core-data';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, Notice } from '@wordpress/components';
import { __, _n } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import NumberControl from '../editor-components/number-control';
import { DIFFICULTIES } from './constants';

const courseThemeEnabled = window?.sensei?.courseThemeEnabled || false;

const LessonPropertiesEdit = ( props ) => {
	const { className } = props;

	const [ meta, setMeta ] = useEntityProp( 'postType', 'lesson', 'meta' );
	const { _lesson_complexity: difficulty = '', _lesson_length: length = 10 } =
		meta || {};
	const readOnly = ! meta;

	const handlePostMetaChange = useCallback(
		( key, value ) => {
			setMeta( {
				...meta,
				[ key ]: value,
			} );
		},
		[ meta, setMeta ]
	);

	return (
		<>
			{ ! readOnly && (
				<InspectorControls>
					<PanelBody title={ __( 'Properties', 'sensei-lms' ) }>
						<NumberControl
							id="sensei-lesson-length"
							label={ __( 'Length', 'sensei-lms' ) }
							min={ 0 }
							step={ 1 }
							value={ length }
							onChange={ ( newLength ) =>
								handlePostMetaChange(
									'_lesson_length',
									newLength
								)
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
							options={ DIFFICULTIES.map(
								( { label, value } ) => ( {
									label,
									value,
								} )
							) }
							value={ difficulty }
							onChange={ ( newComplexity ) =>
								handlePostMetaChange(
									'_lesson_complexity',
									newComplexity
								)
							}
						/>
					</PanelBody>
				</InspectorControls>
			) }

			{ courseThemeEnabled ? (
				<Notice status="warning" isDismissible={ false }>
					{ __(
						'Since Learning Mode is activated, use this block to add the properties to each lesson and make sure your Lesson template contains the Lesson Properties block.',
						'sensei-lms'
					) }
				</Notice>
			) : (
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
			) }
		</>
	);
};

export default LessonPropertiesEdit;
