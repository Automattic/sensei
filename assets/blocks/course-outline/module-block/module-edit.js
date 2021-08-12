/**
 * External dependencies
 */
import classnames from 'classnames';
import AnimateHeight from 'react-animate-height';

/**
 * WordPress dependencies
 */
import { InnerBlocks, RichText } from '@wordpress/block-editor';
import { Icon } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { useContext, useState } from '@wordpress/element';
import { dispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { chevronUp } from '../../../icons/wordpress-icons';
import {
	withColorSettings,
	withDefaultColor,
} from '../../../shared/blocks/settings';
import { useAutoInserter } from '../../../shared/blocks/use-auto-inserter';
import { OutlineAttributesContext } from '../outline-block/outline-edit';
import SingleLineInput from '../../../shared/blocks/single-line-input';
import { ModuleStatus } from './module-status';
import ModuleSettings from './module-settings';

const ALLOWED_BLOCKS = [ 'sensei-lms/course-outline-lesson' ];

/**
 * Edit module block component.
 *
 * @param {Object}   props                             Component props.
 * @param {string}   props.clientId                    The module block id.
 * @param {string}   props.className                   Custom class name.
 * @param {Object}   props.attributes                  Block attributes.
 * @param {string}   props.attributes.title            Module title.
 * @param {string}   props.attributes.description      Module description.
 * @param {boolean}  props.attributes.borderedSelected The border setting selected by the user.
 * @param {string}   props.attributes.borderColorValue The border color.
 * @param {Object}   props.mainColor                   Header main color.
 * @param {Object}   props.defaultMainColor            Default main color.
 * @param {Object}   props.textColor                   Header text color.
 * @param {Object}   props.defaultTextColor            Default text color.
 * @param {Object}   props.defaultBorderColor          Default border color.
 * @param {Function} props.setAttributes               Block set attributes function.
 * @param {string}   props.name                        Name of the block.
 */
export const ModuleEdit = ( props ) => {
	const {
		clientId,
		className,
		attributes: { title, description, borderedSelected, borderColorValue },
		mainColor,
		defaultMainColor,
		textColor,
		defaultTextColor,
		defaultBorderColor,
		setAttributes,
	} = props;
	const {
		outlineAttributes: {
			collapsibleModules,
			moduleBorder: outlineBordered,
		},
		outlineClassName,
	} = useContext( OutlineAttributesContext ) || {
		outlineAttributes: {},
		outlineClassName: '',
	};

	const isEmptyBlock = ( attributes ) => ! attributes.title;

	useAutoInserter(
		{ name: 'sensei-lms/course-outline-lesson', isEmptyBlock },
		props
	);

	/**
	 * Handle update name.
	 *
	 * @param {string} value Name value.
	 */
	const updateName = ( value ) => {
		setAttributes( { title: value } );
	};

	/**
	 * Handle update description.
	 *
	 * @param {string} value Description value.
	 */
	const updateDescription = ( value ) => {
		setAttributes( { description: value } );
	};

	const [ isExpanded, setExpanded ] = useState( true );

	const styleRegex = /is-style-(\w+)/;
	const style =
		className.match( styleRegex )?.[ 1 ] ||
		outlineClassName.match( styleRegex )?.[ 1 ];

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

	// Minimal border element.
	let minimalBorder;
	if ( 'minimal' === style ) {
		minimalBorder = (
			<div
				className="wp-block-sensei-lms-course-outline-module__name__minimal-border"
				style={ {
					background: mainColor?.color || defaultMainColor?.color,
				} }
			/>
		);
	}

	const bordered =
		undefined !== borderedSelected ? borderedSelected : outlineBordered;

	return (
		<>
			<ModuleSettings
				bordered={ bordered }
				setBordered={ ( newValue ) =>
					setAttributes( { borderedSelected: newValue } )
				}
			/>
			<section
				className={ classnames( className, {
					'wp-block-sensei-lms-course-outline-module-bordered': bordered,
				} ) }
				style={ {
					borderColor: borderColorValue || defaultBorderColor?.color,
				} }
			>
				<header
					className="wp-block-sensei-lms-course-outline-module__header"
					style={ headerStyles }
				>
					<h2 className="wp-block-sensei-lms-course-outline-module__title">
						<SingleLineInput
							className="wp-block-sensei-lms-course-outline-module__title-input"
							placeholder={ __( 'Module name', 'sensei-lms' ) }
							value={ title }
							onChange={ updateName }
						/>
					</h2>
					<ModuleStatus clientId={ clientId } />
					{ collapsibleModules && (
						<button
							type="button"
							className={ classnames(
								'wp-block-sensei-lms-course-outline__arrow',
								{ collapsed: ! isExpanded }
							) }
							onClick={ () => setExpanded( ! isExpanded ) }
						>
							<Icon icon={ chevronUp } />
							<span className="screen-reader-text">
								{ __( 'Toggle module content', 'sensei-lms' ) }
							</span>
						</button>
					) }
				</header>
				{ minimalBorder }
				<AnimateHeight
					className="wp-block-sensei-lms-collapsible"
					duration={ 500 }
					animateOpacity
					height={ ! collapsibleModules || isExpanded ? 'auto' : 0 }
				>
					<div className="wp-block-sensei-lms-course-outline-module__description">
						<RichText
							className="wp-block-sensei-lms-course-outline-module__description-input"
							placeholder={ __(
								'Module description',
								'sensei-lms'
							) }
							value={ description }
							onChange={ updateDescription }
						/>
					</div>
					<h3 className="wp-block-sensei-lms-course-outline-module__lessons-title">
						{ __( 'Lessons', 'sensei-lms' ) }
					</h3>
					<InnerBlocks
						allowedBlocks={ ALLOWED_BLOCKS }
						templateInsertUpdatesSelection={ false }
						renderAppender={ () => null }
					/>
				</AnimateHeight>
			</section>
		</>
	);
};

export default compose(
	withColorSettings( {
		mainColor: {
			style: 'background-color',
			label: __( 'Main color', 'sensei-lms' ),
		},
		textColor: { style: 'color', label: __( 'Text color', 'sensei-lms' ) },
		borderColor: {
			style: 'border-color',
			label: __( 'Border color', 'sensei-lms' ),
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
)( ModuleEdit );
