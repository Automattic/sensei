/**
 * External dependencies
 */
import classNames from 'classnames';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, help, lock, external } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import Section from '../section';

const Item = ( { title, url, extraLink } ) => {
	const isLinkEnabled = url !== null;

	const link = isLinkEnabled ? (
		<a href={ url } target="_blank" rel="noreferrer">
			{ title }
		</a>
	) : (
		title
	);

	return (
		<li className="sensei-home__help-item">
			<div className="sensei-home__help-item__icon">
				<Icon icon={ isLinkEnabled ? help : lock } size={ 16 } />
			</div>
			<div
				className={ classNames( 'sensei-home__help-item__title', {
					'sensei-home__help-item__title--disabled': ! isLinkEnabled,
				} ) }
			>
				{ link }
			</div>
			{ extraLink && (
				<div className="sensei-home__help-item__extra-link">
					<a href={ extraLink.url } target="_blank" rel="noreferrer">
						{ extraLink.label }{ ' ' }
						<Icon icon={ external } size={ 10 } />
					</a>
				</div>
			) }
		</li>
	);
};

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
 * @param {Object} props            Properties.
 * @param {Object} props.categories The actual data.
 */
const GetHelp = ( { categories } ) => {
	if ( categories === undefined ) {
		return null;
	}

	return (
		<Section title={ __( 'Get help', 'sensei-lms' ) }>
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
