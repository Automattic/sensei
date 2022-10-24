/**
 * External dependencies
 */
import classNames from 'classnames';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, help } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import Section from '../section';
import LockIcon from '../../icons/lock.svg';
import Link from '../link';

/**
 * Component representing each of the items under a Help Category.
 *
 * @param {Object}      props           Component properties.
 * @param {string}      props.title     The title.
 * @param {string|null} props.url       Optional url. When missing the title will not be clickable.
 * @param {Object|null} props.extraLink An extra link that will be displayed next to the main title.
 */
const Item = ( { title, url, extraLink } ) => {
	const isTitleInteractive = url !== null;

	return (
		<li className="sensei-home__help-item">
			<div className="sensei-home__help-item__icon">
				{ isTitleInteractive ? (
					<Icon icon={ help } />
				) : (
					<LockIcon
						className={ 'sensei-home__help-item__icon__lock' }
					/>
				) }
			</div>
			<div
				className={ classNames( 'sensei-home__help-item__title', {
					'sensei-home__help-item__title--disabled': ! isTitleInteractive,
				} ) }
			>
				{ isTitleInteractive ? (
					<Link label={ title } url={ url } />
				) : (
					<span>{ title }</span>
				) }
				{ extraLink && (
					<Link label={ extraLink.label } url={ extraLink.url } />
				) }
			</div>
		</li>
	);
};

/**
 * Help Category component. It's composed by a title and a list of items.
 *
 * @param {Object}   props       Component properties.
 * @param {string}   props.title The title.
 * @param {Object[]} props.items List of items under the specific category.
 */
const Category = ( { title, items } ) => {
	return (
		<div className="sensei-home__help-category">
			<h3>{ title }</h3>
			<ul>
				{ items.map( ( item, itemIndex ) => (
					<Item
						key={ itemIndex }
						title={ item.title }
						url={ item.url }
						icon={ item.icon }
						extraLink={ item.extra_link }
					/>
				) ) }
			</ul>
		</div>
	);
};

/**
 * Get Help section component.
 *
 * @param {Object}   props            Properties.
 * @param {Object[]} props.categories A list of categories and its items.
 */
const GetHelp = ( { categories } ) => {
	if ( categories === undefined ) {
		return null;
	}

	return (
		<Section title={ __( 'Get Help', 'sensei-lms' ) }>
			{ categories.map( ( category, categoryIndex ) => (
				<Category
					key={ categoryIndex }
					title={ category.title }
					items={ category.items }
				/>
			) ) }
		</Section>
	);
};

export default GetHelp;
