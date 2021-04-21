/**
 * WordPress dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
} from '@wordpress/components';
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
import { COMPLEXITIES } from './constants';

const LessonMetadataEdit = ( props ) => {
	const {
		className,
		attributes: {
			complexity,
			length,
		},
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
						onChange={ length => setAttributes( { length } ) }
						suffix={ _n( 'minute', 'minutes', length, 'sensei-lms' ) }
					/>

					<SelectControl
						label={ __( 'Complexity', 'sensei-lms' ) }
						options={ COMPLEXITIES.map(
							( { label, value } ) => ( { label, value } )
						) }
						value={ complexity }
						onChange={ complexity => setAttributes( { complexity } ) }
					/>
				</PanelBody>
			</InspectorControls>

			<div className={ classnames( 'lesson-metadata', className ) }>
				{ !! length && (
					<span className="lesson-length">
						{ __( 'Length', 'sensei-lms' ) + ': ' + length + ' ' + _n( 'minute', 'minutes', length, 'sensei-lms' ) }
					</span>
				) }

				{ complexity && (
					<span className="lesson-complexity">
						{
							__( 'Complexity', 'sensei-lms' ) + ': ' +
							COMPLEXITIES.find( lessonComplexity => complexity === lessonComplexity.value )?.label
						}
					</span>
				) }
			</div>
		</ToggleLegacyLessonMetaboxesWrapper>
	);
};

export default LessonMetadataEdit;
