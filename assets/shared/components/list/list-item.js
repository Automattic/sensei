/**
 * WordPress dependencies
 */
import { ENTER } from '@wordpress/keycodes';

function handleKeyDown( event, onClick ) {
	if ( typeof onClick === 'function' && event.keyCode === ENTER ) {
		onClick();
	}
}

function getItemLinkType( item ) {
	const { href, linkType } = item;

	if ( linkType ) {
		return linkType;
	}

	return href ? 'external' : null;
}

/**
 * List component to display a list of items.
 *
 * @param {Object} props props for list item
 */
function ListItem( props ) {
	const { item } = props;
	const {
		before,
		title,
		after,
		content,
		onClick,
		href,
		target,
		listItemTag,
	} = item;
	const hasAction = typeof onClick === 'function' || href;
	const InnerTag = href ? 'a' : 'div';

	const innerTagProps = {
		className: 'sensei-list__item-inner',
		onClick: typeof onClick === 'function' ? onClick : null,
		'aria-disabled': hasAction ? 'false' : null,
		tabIndex: hasAction ? '0' : null,
		role: hasAction ? 'menuitem' : null,
		onKeyDown: ( e ) => ( hasAction ? handleKeyDown( e, onClick ) : null ),
		target: href ? target : null,
		type: getItemLinkType( item ),
		href,
		'data-list-item-tag': listItemTag,
	};

	return (
		<InnerTag { ...innerTagProps }>
			{ before && (
				<div className="sensei-list__item-before">{ before }</div>
			) }
			<div className="sensei-list__item-text">
				<span className="sensei-list__item-title">{ title }</span>
				{ content && (
					<span className="sensei-list__item-content">
						{ content }
					</span>
				) }
			</div>
			{ after && (
				<div className="sensei-list__item-after">{ after }</div>
			) }
		</InnerTag>
	);
}

export default ListItem;
