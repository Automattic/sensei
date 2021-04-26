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
import ExtensionActions from './extension-actions';

/**
 * Extensions card component.
 *
 * @param {Object}   props             Component props.
 * @param {string}   props.title       Card title (extension title will be used as fallback).
 * @param {string}   props.excerpt     Card excerpt (extension excerpt will be used as fallback).
 * @param {string}   props.badgeLabel  Badge label (will check extension update if it's not defined).
 * @param {Object[]} props.customLinks Array with custom links containing the link props.
 * @param {string}   props.image       Card image.
 * @param {Object}   props.extension   Extension object.
 * @param {Object}   props.htmlProps   Wrapper extra props.
 */
const Card = ( {
	title,
	excerpt,
	badgeLabel,
	customLinks,
	image,
	extension,
	htmlProps,
} ) => {
	const backgroundImage =
		( image && `url(${ image })` ) ||
		( extension?.image && `url(${ extension.image })` );
	return (
		<article
			{ ...htmlProps }
			className={ classnames(
				'sensei-extensions__card',
				htmlProps?.className
			) }
		>
			<div
				className="sensei-extensions__card__image"
				style={ {
					backgroundImage,
				} }
			/>
			<div className="sensei-extensions__card__content">
				<header className="sensei-extensions__card__header">
					<h3 className="sensei-extensions__card__title">
						{ title || extension.title }
					</h3>
					{ ( badgeLabel || extension?.[ 'has_update' ] ) && (
						<small className="sensei-extensions__card__new-badge">
							{ badgeLabel || __( 'New version', 'sensei-lms' ) }
						</small>
					) }
				</header>
				<div className="sensei-extensions__card__body">
					<p className="sensei-extensions__card__description">
						{ excerpt || extension.excerpt }
					</p>
					{ ( extension || customLinks ) && (
						<ExtensionActions
							extension={ extension }
							customLinks={ customLinks }
						/>
					) }
				</div>
			</div>
		</article>
	);
};

export default Card;
