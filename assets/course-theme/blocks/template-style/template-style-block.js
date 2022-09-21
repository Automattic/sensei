/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon } from '@wordpress/components';
import { brush } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import blockMeta from './template-style.block.json';

const TemplateStyleBlockEditSave = ( { attributes } ) => (
	<style dangerouslySetInnerHTML={ { __html: attributes.content } } />
);

export const templateStyleBlock = {
	...blockMeta,
	title: __( 'Template Style', 'sensei-lms' ),
	icon: {
		src: <Icon icon={ brush } />,
		foreground: '#43AF99',
	},
	edit: TemplateStyleBlockEditSave,
	save: TemplateStyleBlockEditSave,
};
