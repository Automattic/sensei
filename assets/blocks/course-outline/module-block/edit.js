import {
	InnerBlocks,
	RichText,
	withColors,
	InspectorControls,
} from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

import { PanelBody } from '@wordpress/components';
import { useState, useContext } from '@wordpress/element';
import classnames from 'classnames';
import { OutlineAttributesContext } from '../course-block/edit';

import SingleLineInput from '../single-line-input';
import { ModuleBlockSettings } from './settings';
import { ModuleBlockSettings } from './settings';

/**
 * Edit module block component.
 *
 * @param {Object}   props                        Component props.
 * @param {string}   props.className              Custom class name.
 * @param {Object}   props.attributes             Block attributes.
 * @param {string}   props.attributes.title       Module title.
 * @param {string}   props.attributes.description Module description.
 * @param {Object}   props.attributes.style       Custom visual settings.
 * @param {Function} props.setAttributes          Block set attributes function.
 */
const EditModuleBlock = ( props ) => {
	const {
		className,
		attributes: { title, description },
		mainColor,
		setAttributes,
	} = props;
	const {
		      outlineAttributes: { animationsEnabled },
	      } = useContext( OutlineAttributesContext );
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

	const [ isPreviewCompleted, setIsPreviewCompleted ] = useState( false );

	let indicatorText = __( 'In Progress', 'sensei-lms' );
	let indicatorClass = null;

	if ( isPreviewCompleted ) {
		indicatorText = __( 'Completed', 'sensei-lms' );
		indicatorClass = 'completed';
	}

	const [ isExpanded, setExpanded ] = useState( true );

	function handleKeyDown( e ) {
		if ( 13 === e.keyCode ) {
			setExpanded( ! isExpanded );
		}
	}

	const isDefaultStyle =
		className.includes( 'is-style-default' ) ||
		! className.includes( 'is-style-' );

	return (
		<>
			<ModuleBlockSettings
				isPreviewCompleted={ isPreviewCompleted }
				setIsPreviewCompleted={ setIsPreviewCompleted }
			/>
			<ModuleBlockSettings { ...props } />
			<section
				className={ classnames( className, {
					'is-style-default': isDefaultStyle,
				} ) }
				style={
					isDefaultStyle
						? {
								borderColor: mainColor.color,
						  }
						: {}
				}
			>
				<header
					className="wp-block-sensei-lms-course-outline-module__name"
					style={
						isDefaultStyle
							? { background: mainColor.color }
							: { borderColor: mainColor.color }
					}
				>
					<h2 className="wp-block-sensei-lms-course-outline__clean-heading">
						<SingleLineInput
							className="wp-block-sensei-lms-course-outline-module__name-input"
							placeholder={ __( 'Module name', 'sensei-lms' ) }
							value={ title }
							onChange={ updateName }
						/>
					</h2>
					<div
						className={ classnames(
							'wp-block-sensei-lms-course-outline__progress-indicator',
							indicatorClass
						) }
					>
						<span className="wp-block-sensei-lms-course-outline__progress-indicator__text">
							{ indicatorText }
						</span>
					</div>
					<div
						className={ classnames(
							'wp-block-sensei-lms-course-outline__arrow',
							'dashicons',
							isExpanded
								? 'dashicons-arrow-up-alt2'
								: 'dashicons-arrow-down-alt2'
						) }
						onClick={ () => setExpanded( ! isExpanded ) }
						onKeyDown={ handleKeyDown }
						role="button"
						tabIndex={ 0 }
					/>
				</header>
				<div
					className={ classnames(
						'wp-block-sensei-lms-collapsible',
						{ animated: animationsEnabled },
						{ collapsed: ! isExpanded }
					) }
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
					<div className="wp-block-sensei-lms-course-outline-module__lessons-title">
						<h3 className="wp-block-sensei-lms-course-outline__clean-heading">
							{ __( 'Lessons', 'sensei-lms' ) }
						</h3>
					</div>
					<InnerBlocks
						template={ [
							[ 'sensei-lms/course-outline-lesson', {} ],
						] }
						allowedBlocks={ [ 'sensei-lms/course-outline-lesson' ] }
					/>
				</div>
			</section>
		</>
	);
};

export default withColors( {
	mainColor: 'background-color',
	textColor: 'color',
} )( EditModuleBlock );
