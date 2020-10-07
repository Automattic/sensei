import {
	RichText,
	InspectorControls,
	BlockControls,
	AlignmentToolbar,
} from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import { PanelBody, RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { useCallback } from '@wordpress/element';

const MIN_BORDER_RADIUS_VALUE = 0;
const MAX_BORDER_RADIUS_VALUE = 50;
const INITIAL_BORDER_RADIUS_POSITION = 5;

function BorderPanel( { borderRadius = '', setAttributes } ) {
	const initialBorderRadius = borderRadius;
	const setBorderRadius = useCallback(
		( newBorderRadius ) => {
			if ( newBorderRadius === undefined )
				setAttributes( {
					borderRadius: initialBorderRadius,
				} );
			else setAttributes( { borderRadius: newBorderRadius } );
		},
		[ initialBorderRadius, setAttributes ]
	);
	return (
		<PanelBody title={ __( 'Border settings', 'sensei-lms' ) }>
			<RangeControl
				value={ borderRadius }
				label={ __( 'Border radius', 'sensei-lms' ) }
				min={ MIN_BORDER_RADIUS_VALUE }
				max={ MAX_BORDER_RADIUS_VALUE }
				initialPosition={ INITIAL_BORDER_RADIUS_POSITION }
				allowReset
				onChange={ setBorderRadius }
			/>
		</PanelBody>
	);
}

const EditButton = ( props ) => {
	const { attributes, setAttributes, className, colorProps } = props;
	const { borderRadius, placeholder, text, align } = attributes;
	return (
		<div
			className={ classnames( className, {
				[ `has-text-align-${ align }` ]: align,
			} ) }
		>
			<RichText
				placeholder={ placeholder || __( 'Add text…', 'sensei-lms' ) }
				value={ text }
				onChange={ ( value ) => setAttributes( { text: value } ) }
				withoutInteractiveFormatting
				className={ classnames(
					'wp-block-button__link',
					colorProps?.className,
					{
						'no-border-radius': borderRadius === 0,
					}
				) }
				style={ {
					borderRadius: borderRadius
						? borderRadius + 'px'
						: undefined,
					...colorProps?.style,
				} }
				identifier="text"
			/>
			<BlockControls>
				<AlignmentToolbar
					value={ align }
					onChange={ ( nextAlign ) => {
						setAttributes( { align: nextAlign } );
					} }
				/>
			</BlockControls>

			<InspectorControls>
				<BorderPanel
					borderRadius={ borderRadius }
					setAttributes={ setAttributes }
				/>
			</InspectorControls>
		</div>
	);
};

const edit = EditButton;

registerBlockType( 'sensei-lms/button-own', {
	name: 'sensei-lms/button-own',
	title: 'Take Course Own',
	category: 'sensei-lms',
	attributes: {
		text: {
			type: 'string',
			source: 'html',
			selector: 'a',
			default: 'Take Course',
		},
		align: {
			type: 'string',
		},
		borderRadius: {
			type: 'number',
		},
		style: {
			type: 'object',
		},
	},
	supports: {
		align: false,
		html: false,
	},
	edit,
	save( { attributes } ) {
		const { borderRadius, linkTarget, rel, text, title, url } = attributes;
		//const colorProps = getColorAndStyleProps( attributes );
		const buttonClasses = classnames(
			'wp-block-button__link',
			//colorProps.className,
			{
				'no-border-radius': borderRadius === 0,
			}
		);
		const buttonStyle = {
			borderRadius: borderRadius ? borderRadius + 'px' : undefined,
			//...colorProps.style,
		};
		return (
			<div className="wp-block-button">
				<RichText.Content
					tagName="a"
					className={ buttonClasses }
					href={ url }
					title={ title }
					style={ buttonStyle }
					value={ text }
					target={ linkTarget }
					rel={ rel }
				/>
			</div>
		);
	},
} );
