/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Platform } from '@wordpress/element';
import {
	BlockControls,
	RichText,
	useBlockProps,
	HeadingLevelDropdown,
	useBlockEditingMode,
} from '@wordpress/block-editor';

export const CourseTitleEdit = ( {
	attributes,
	setAttributes,
	mergeBlocks,
	onReplace,
	style,
} ) => {
	const { content, level, levelOptions, className, placeholder } = attributes;
	const tagName = 'h' + level;
	const blockProps = useBlockProps( {
		className,
		style,
	} );
	const blockEditingMode = useBlockEditingMode();

	return (
		<>
			{ blockEditingMode === 'default' && (
				<BlockControls group="block">
					<HeadingLevelDropdown
						value={ level }
						options={ levelOptions }
						onChange={ ( newLevel ) =>
							setAttributes( { level: newLevel } )
						}
					/>
				</BlockControls>
			) }
			<RichText
				identifier="content"
				tagName={ tagName }
				value={ content }
				onMerge={ mergeBlocks }
				onReplace={ onReplace }
				onRemove={ () => onReplace( [] ) }
				placeholder={
					placeholder || __( 'Course Title', 'sensei-lms' )
				}
				{ ...( Platform.isNative && { deleteEnter: true } ) } // setup RichText on native mobile to delete the "Enter" key as it's handled by the JS/RN side
				{ ...blockProps }
			/>
		</>
	);
};
