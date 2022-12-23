/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { RichText, useBlockProps } from '@wordpress/block-editor';
/**
 * External dependencies
 */
import type { SyntheticEvent } from 'react';

export const cancelOnSpacingPress = ( e: KeyboardEvent ) => {
	if ( e.key === ' ' ) e.preventDefault();
};

export const openOnlyWhenIsSummaryTag = ( e: SyntheticEvent ) =>
	( e.target as Element ).tagName !== 'SUMMARY' && e.preventDefault();

export const cancelEnterKey = ( e: {
	key: string;
	preventDefault: () => void;
} ) => {
	if ( e.key === 'Enter' ) e.preventDefault();
};

export const Summary = ( props: {
	attributes: any;
	setAttributes: any;
} ): JSX.Element => {
	const { attributes, setAttributes } = props;
	const { summary } = attributes;
	const blockProps = useBlockProps();

	return (
		<summary
			{ ...blockProps }
			onClick={ openOnlyWhenIsSummaryTag }
			onKeyUp={ cancelOnSpacingPress }
			onKeyDownCapture={ cancelEnterKey }
		>
			<RichText
				placeholder={ __( 'Add title', 'sensei-lms' ) }
				identifier="summary"
				tagName="h3"
				value={ summary }
				multiline={ false }
				withoutInteractiveFormatting
				onChange={ ( value: string ) =>
					setAttributes( { summary: value } )
				}
			/>
		</summary>
	);
};

export default Summary;
