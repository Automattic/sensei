/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { ExternalLink } from '@wordpress/components';

const PROMO_LINK =
	'https://senseilms.com/sensei-pro/?utm_source=plugin_sensei&utm_medium=upsell&utm_campaign=quiz_ordering_question_type';

function addOrderingPromoOption( options ) {
	options.push( {
		title: __( 'Ordering', 'sensei-lms' ),
		description: __(
			'Place the answers in the correct order.',
			'sensei-lms'
		),
		label: __( 'Ordering', 'sensei-lms' ),
		value: 'ordering',
		disabled: true,
	} );
	return options;
}

addFilter(
	'senseiQuestionTypeToolbarOptions',
	'sensei-lms/ordering-promo',
	addOrderingPromoOption
);

function addPromoLink( children, option ) {
	if ( option.value !== 'ordering' ) {
		return children;
	}

	return (
		<div className="sensei-lms-question-block__type-selector__option__container--disabled">
			<strong> { option.title }</strong>
			<div className="sensei-lms-question-block__type-selector__option__description sensei-lms-question-block__type-selector__option__description--disabled">
				{ option.description }
			</div>
			<ExternalLink href={ PROMO_LINK }>
				{ __( 'Upgrade to Sensei Pro', 'sensei-lms' ) }
			</ExternalLink>
		</div>
	);
}

addFilter(
	'senseiQuestionTypeToolbarOptionChildren',
	'sensei-lms/ordering-promo',
	addPromoLink
);
