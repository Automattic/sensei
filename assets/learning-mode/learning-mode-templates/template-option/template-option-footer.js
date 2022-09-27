/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { TemplateOptionTitle } from './template-option-title';
import { TemplateActions } from '../template-actions';

/**
 * Renders the Learning Mode template option's footer.
 *
 * @param {Object} props
 * @param {string} props.name       The name of the template.
 * @param {string} props.title      The title of the template.
 * @param {string} props.isActive   Tells if the current template is activated.
 * @param {Object} props.upsell     The upsell data.
 * @param {string} props.upsell.tag The upsell tag.
 */
export const TemplateOptionFooter = ( props ) => {
	const { title, isActive, upsell } = props;
	return (
		<div
			className={ classnames( {
				'sensei-lm-template-option__footer': true,
				'sensei-lm-template-option__footer--active': isActive,
			} ) }
		>
			<TemplateOptionTitle
				isActive={ isActive }
				tag={ isActive ? title : upsell?.tag }
			>
				{ isActive ? __( 'Active', 'sensei-lms' ) : title }
			</TemplateOptionTitle>

			<div className="sensei-lm-template-option__actions">
				<TemplateActions { ...props } />
			</div>
		</div>
	);
};
