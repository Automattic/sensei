/**
 * WordPress dependencies
 */
import { RawHTML } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import SenseiCircleLogo from '../images/sensei-circle-logo.svg';
import Link from './link';

/**
 * Component to render an action of a given notice.
 *
 * @param {Object} props        Component props.
 * @param {Object} props.action The action to render.
 */
const NoticeAction = ( { action } ) => {
	if ( ! action || ! action.label || ! action.url ) {
		return null;
	}

	const isPrimary = action.primary ?? true;

	const buttonClass = isPrimary ? 'button-primary' : 'button-secondary';
	return (
		<a
			href={ action.url }
			target={ action.target ?? '_blank' }
			rel="noopener noreferrer"
			className={ classnames( 'button', buttonClass ) }
		>
			{ action.label }
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
	if ( ! actions || ! Array.isArray( actions ) ) {
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
 * Renders the heading of a given notice, wrapped with a proper CSS class, or null if there's no heading.
 *
 * @param {Object} props         Component props.
 * @param {string} props.heading The heading to render.
 */
const NoticeHeading = ( { heading } ) => {
	if ( ! heading ) {
		return null;
	}
	return <div className="sensei-notice__heading">{ heading }</div>;
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
	return (
		<Link label={ decodeEntities( infoLink.label ) } url={ infoLink.url } />
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
	if ( !! notice.style ) {
		noticeClass = 'sensei-notice-' + notice.style;
	}

	const isDismissible = notice.dismissible && dismissNonce;

	const containerProps = {
		className: classnames( 'notice', 'sensei-notice', noticeClass, {
			'is-dismissible': isDismissible,
		} ),
	};

	if ( isDismissible ) {
		containerProps[ 'data-dismiss-action' ] = 'sensei_dismiss_notice';
		containerProps[ 'data-dismiss-notice' ] = noticeId;
		containerProps[ 'data-dismiss-nonce' ] = dismissNonce;
	}

	return (
		<div { ...containerProps }>
			<div className="sensei-notice__wrapper">
				<div className="sensei-notice__content">
					<SenseiCircleLogo className="sensei-notice__logo" />
					<NoticeHeading heading={ notice.heading } />
					<RawHTML>{ notice.message }</RawHTML>
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
