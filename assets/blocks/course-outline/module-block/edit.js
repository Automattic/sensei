import { InnerBlocks, RichText } from '@wordpress/block-editor';
import { Icon } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { useContext, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import AnimateHeight from 'react-animate-height';
import { chevronUp } from '../../../icons/wordpress-icons';

import {
	withColorSettings,
	withDefaultBlockStyle,
} from '../../../shared/blocks/settings';
import { OutlineAttributesContext } from '../course-block/edit';
import SingleLineInput from '../single-line-input';
import { ModuleStatus } from './module-status';
import { ModuleBlockSettings } from './settings';

/**
 * Edit module block component.
 *
 * @param {Object}   props                        Component props.
 * @param {string}   props.className              Custom class name.
 * @param {Object}   props.attributes             Block attributes.
 * @param {string}   props.attributes.title       Module title.
 * @param {string}   props.attributes.description Module description.
 * @param {Object}   props.mainColor              Header main color.
 * @param {Object}   props.textColor              Header text color.
 * @param {string}   props.attributes.blockStyle  Selected block style.
 * @param {Function} props.setAttributes          Block set attributes function.
 */
export const EditModuleBlock = ( props ) => {
	const {
		className,
		attributes: { title, description },
		mainColor,
		textColor,
		setAttributes,
		blockStyle,
	} = props;
	const {
		outlineAttributes: { collapsibleModules },
	} = useContext( OutlineAttributesContext ) || { outlineAttributes: {} };
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

	const blockStyleColors = {
		default: { background: mainColor?.color },
		minimal: { borderColor: mainColor?.color },
	}[ blockStyle ];

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
			<h3 className="wp-block-sensei-lms-course-outline-module__lessons-title">
				{ __( 'Lessons', 'sensei-lms' ) }
			</h3>
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
			<ModuleBlockSettings { ...props } />
			<section className={ className }>
				<header
					className="wp-block-sensei-lms-course-outline-module__header"
					style={ { ...blockStyleColors, color: textColor?.color } }
				>
					<h2 className="wp-block-sensei-lms-course-outline-module__title">
						<SingleLineInput
							className="wp-block-sensei-lms-course-outline-module__title-input"
							placeholder={ __( 'Module name', 'sensei-lms' ) }
							value={ title }
							onChange={ updateName }
						/>
					</h2>
					<ModuleStatus />
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

				{ collapsibleModules ? animationWrapper : moduleContent }
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
	} ),
	withDefaultBlockStyle()
)( EditModuleBlock );
