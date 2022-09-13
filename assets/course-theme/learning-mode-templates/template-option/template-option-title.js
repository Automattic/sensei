/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Renders a template option title.
 *
 * @param {Object}  props
 * @param {string}  props.children The title.
 * @param {string}  props.tag      A tag for template to indicate it belongs to some group of templates. E.g.: "PREMIUM"
 * @param {boolean} props.isActive If the template is activated.
 */
export const TemplateOptionTitle = ( { children, isActive, tag = '' } ) => {
	return (
		<h4
			className={ classnames( {
				'sensei-lm-template-option__title': true,
				'sensei-lm-template-option__title--active': isActive,
			} ) }
		>
			{ children }
			{ tag && (
				<>
					{ ': ' }
					<span className="sensei-lm-template-option__title-tag">
						{ tag }
					</span>
				</>
			) }
		</h4>
	);
};
