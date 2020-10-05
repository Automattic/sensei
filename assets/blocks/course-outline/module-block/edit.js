import { __ } from '@wordpress/i18n';
import { InnerBlocks, RichText } from '@wordpress/block-editor';
import { useState, useContext } from '@wordpress/element';
import classnames from 'classnames';
import AnimateHeight from 'react-animate-height';

import { OutlineAttributesContext } from '../course-block/edit';
import SingleLineInput from '../single-line-input';
import { ModuleBlockSettings } from './settings';

/**
 * Edit module block component.
 *
 * @param {Object}   props                        Component props.
 * @param {string}   props.className              Custom class name.
 * @param {Object}   props.attributes             Block attributes.
 * @param {string}   props.attributes.title       Module title.
 * @param {string}   props.attributes.description Module description.
 * @param {Function} props.setAttributes          Block set attributes function.
 */
const EditModuleBlock = ( {
	className,
	attributes: { title, description },
	setAttributes,
} ) => {
	const {
		outlineAttributes: { collapsibleModules },
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

	const moduleContent = (
		<>
			<div className="wp-block-sensei-lms-course-outline-module__description">
				<RichText
					className="wp-block-sensei-lms-course-outline-module__description-input"
					placeholder={ __( 'Module description', 'sensei-lms' ) }
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
				template={ [ [ 'sensei-lms/course-outline-lesson', {} ] ] }
				allowedBlocks={ [ 'sensei-lms/course-outline-lesson' ] }
				templateInsertUpdatesSelection={ false }
			/>
		</>
	);

	const animationWrapper = collapsibleModules && (
		<AnimateHeight
			className="wp-block-sensei-lms-collapsible"
			duration={ 500 }
			animateOpacity
			height={ isExpanded ? 'auto' : 0 }
		>
			{ moduleContent }
		</AnimateHeight>
	);

	return (
		<>
			<ModuleBlockSettings
				isPreviewCompleted={ isPreviewCompleted }
				setIsPreviewCompleted={ setIsPreviewCompleted }
			/>

			<section className={ className }>
				<header className="wp-block-sensei-lms-course-outline-module__name">
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
					{ collapsibleModules && (
						<button
							type="button"
							className={ classnames(
								'wp-block-sensei-lms-course-outline__arrow',
								'dashicons',
								isExpanded
									? 'dashicons-arrow-up-alt2'
									: 'dashicons-arrow-down-alt2'
							) }
							onClick={ () => setExpanded( ! isExpanded ) }
						>
							<span className="screen-reader-text">
								{ __( 'Toggle module content', 'sensei-lms' ) }
							</span>
						</button>
					) }
				</header>

				{ collapsibleModules ? animationWrapper : moduleContent }
			</section>
		</>
	);
};

export default EditModuleBlock;
