/**
 * WordPress dependencies
 */
import { RawHTML } from '@wordpress/element';

/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import SenseiCircleLogo from '../images/sensei-circle-logo.svg';
import Link from './link';

const hiddenClassName = 'sensei-notice--is-hidden';

/**
 * Component to render an action of a given notice.
 *
 * @param {Object} props        Component props.
 * @param {Object} props.action The action to render.
 */
const NoticeAction = ( { action } ) => {
	if ( ! action || ! action.label || ( ! action.url && ! action.tasks ) ) {
		return null;
	}

	const isPrimary = action.primary ?? true;

	const buttonClass = isPrimary ? 'button-primary' : 'button-secondary';

	const extraProps = {};
	if ( action.tasks ) {
		extraProps[ 'data-sensei-notice-tasks' ] = JSON.stringify(
			action.tasks
		);
	}
	return (
		<a
			href={ action.url }
			target={ action.target ?? '_self' }
			rel="noopener noreferrer"
			className={ classnames( 'button', buttonClass ) }
			{ ...extraProps }
		>
			<RawHTML>{ action.label }</RawHTML>
		</a>
	);
};

/**
 * Component to render a list of actions for a notice based on an array of actions, or nothing if there's no actions.
 *
 * @param {Object}   props         Component props.
 * @param {Object[]} props.actions The list of actions to render.
 */
const NoticeActions = ( { actions } ) => {
	if ( ! actions || ! Array.isArray( actions ) || ! actions.length ) {
		return null;
	}
	return (
		<div className="sensei-notice__actions">
			{ actions.map( ( action ) => (
				<NoticeAction key={ action.url } action={ action } />
			) ) }
		</div>
	);
};

/**
 * Renders the info link of a given notice, or null if there's no info link.
 *
 * @param {Object} props          Component props.
 * @param {Object} props.infoLink The info link to render, if any.
 */
const NoticeInfoLink = ( { infoLink } ) => {
	if ( ! infoLink ) {
		return null;
	}
	const dataSet = {};
	if ( infoLink.tasks ) {
		dataSet[ 'sensei-notice-tasks' ] = JSON.stringify( infoLink.tasks );
	}
	return (
		<Link
			label={ infoLink.label }
			url={ infoLink.url }
			dataSet={ dataSet }
		/>
	);
};

/**
 * Component that renders a single notice.
 *
 * @param {Object} props              Component props.
 * @param {string} props.noticeId     The notice ID.
 * @param {Object} props.notice       The notice data.
 * @param {string} props.dismissNonce The nonce to dismiss notices.
 */
const Notice = ( { noticeId, notice, dismissNonce } ) => {
	let noticeClass = '';
	if ( !! notice.level ) {
		noticeClass = 'sensei-notice-' + notice.level;
	}

	const isDismissible = notice.dismissible && dismissNonce;
	const { parent_id: parentId } = notice;

	const containerProps = {
		className: classnames( 'notice', 'sensei-notice', noticeClass, {
			'is-dismissible': isDismissible,
			[ hiddenClassName ]: !! parentId,
		} ),
		'data-sensei-notice-id': noticeId,
	};

	if ( isDismissible ) {
		containerProps[ 'data-dismiss-action' ] = 'sensei_dismiss_notice';
		containerProps[ 'data-dismiss-notice' ] = parentId ?? noticeId;
		containerProps[ 'data-dismiss-nonce' ] = dismissNonce;
	}

	const message =
		( notice.heading
			? `<div class="sensei-notice__heading">${ notice.heading }</div>`
			: '' ) + notice.message;

	return (
		<div { ...containerProps }>
			<SenseiCircleLogo className="sensei-notice__icon" />
			<div className="sensei-notice__wrapper">
				<div className="sensei-notice__content">
					<RawHTML>{ message }</RawHTML>
				</div>
			</div>
			<NoticeInfoLink infoLink={ notice.info_link } />
			<NoticeActions actions={ notice.actions } />
		</div>
	);
};

/**
 * Component to show a list of notices.
 *
 * @param {Object} props              Component props.
 * @param {Object} props.notices      Mapping of notice IDs => notice object.
 * @param {string} props.dismissNonce A nonce to be able to dismiss things.
 */
const Notices = ( { notices, dismissNonce } ) => {
	return (
		<>
			{ Object.entries( notices ).map( ( [ noticeId, notice ] ) => (
				<Notice
					key={ noticeId }
					notice={ notice }
					noticeId={ noticeId }
					dismissNonce={ dismissNonce }
				/>
			) ) }
		</>
	);
};

export default Notices;
