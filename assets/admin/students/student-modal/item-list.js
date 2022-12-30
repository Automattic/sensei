/**
 * WordPress dependencies
 */
import { CheckboxControl, Spinner } from '@wordpress/components';
import { useCallback, useRef, useState } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { store as coreDataStore } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import useSelectWithDebounce from '../../../react-hooks/use-select-with-debounce';

/**
 * Callback for select or unselect item
 *
 * @callback onChangeEvent
 * @param {boolean} isSelected Describes if the item was selected or unselected
 * @param {Object}  item       Item related to the triggered event
 */

/**
 * Loading list component.
 */
const LoadingItemList = () => (
	<li className="sensei-student-modal__course-list--loading">
		<Spinner />
	</li>
);

/**
 * Empty list component.
 *
 * @param {Object} props
 * @param {Object} props.action Student Modal action.
 */
const EmptyItemList = ( { action } ) => {
	/**
	 * Filters the empty list message in the Item List for Student Modal.
	 *
	 * @since $$next-version$$
	 *
	 * @param {string} emptyListMessage The emptyListMessage to filter.
	 * @param {string} action           Current action for the Student Modal.
	 *
	 * @return {string} Filtered empty list message.
	 */
	const emptyListMessage = applyFilters(
		'senseiStudentModalItemListEmpty',
		__( 'No courses found.', 'sensei-lms' ),
		action
	);

	return (
		<li className="sensei-student-modal__course-list--empty">
			{ emptyListMessage }
		</li>
	);
};

/**
 * List item.
 *
 * @param {Object}        props
 * @param {Object}        props.item     Item
 * @param {boolean}       props.checked  Checkbox state
 * @param {onChangeEvent} props.onChange Event triggered when the an item is select/unselected
 */
const Item = ( { item, checked = false, onChange } ) => {
	const itemId = item?.id;
	const title = decodeEntities( item?.title?.rendered );
	const [ isChecked, setIsChecked ] = useState( checked );

	const onSelectItem = useCallback(
		( isSelected ) => {
			setIsChecked( isSelected );
			onChange( { isSelected, item } );
		},
		[ item, onChange ]
	);

	return (
		<li className="sensei-student-modal__course-list__item" key={ itemId }>
			<CheckboxControl
				id={ `item-${ itemId }` }
				title={ title }
				checked={ isChecked }
				onChange={ onSelectItem }
			/>
			<label htmlFor={ `item-${ itemId }` } title={ title }>
				{ title }
			</label>
		</li>
	);
};

/**
 * Callback for ItemSelection
 *
 * @callback onItemSelectionChange
 * @param {Array} selectedItems List of selected items
 */

/**
 * Item list.
 *
 * @param {Object}                props
 * @param {string}                props.searchQuery Item to search for.
 * @param {onItemSelectionChange} props.onChange    Event triggered when an item is selected or unselected.
 * @param {string}                props.action      The action of the parent modal that requires to load the list.
 */
export const ItemList = ( { searchQuery, onChange, action } ) => {
	const selectedItems = useRef( [] );

	const selectItem = useCallback(
		( { isSelected, item } ) => {
			selectedItems.current = isSelected
				? [ ...selectedItems.current, item ]
				: selectedItems.current.filter( ( c ) => c.id !== item.id );

			onChange( selectedItems.current );
		},
		[ onChange ]
	);

	const { items, isFetching } = useSelectWithDebounce(
		( select ) => {
			const store = select( coreDataStore );

			const defaultQuery = {
				per_page: 100,
				search: searchQuery,
				filter: 'teacher',
			};

			/**
			 * Filters the query to get the item list for Student Modal.
			 *
			 * @since $$next-version$$
			 *
			 * @param {Object} query       The query to get the item list, course list by default.
			 * @param {string} action      Current action for the Student Modal.
			 * @param {string} searchQuery The search query.
			 *
			 * @return {Object} Filtered query to get the item list.
			 */
			const query = applyFilters(
				'senseiStudentModalItemListQuery',
				defaultQuery,
				action,
				searchQuery
			);

			/**
			 * Filters the post type of the items in the list in the Student Modal.
			 *
			 * @since 4.8.0
			 *
			 * @param {string} postType The post type, "course" by default.
			 * @param {string} action   Current action for the Student Modal.
			 *
			 * @return {Object} Filtered post type.
			 */
			const postType = applyFilters(
				'senseiStudentModalItemListPostType',
				'course',
				action
			);

			return {
				items:
					store.getEntityRecords( 'postType', postType, query ) || [],
				isFetching: ! store.hasFinishedResolution( 'getEntityRecords', [
					'postType',
					postType,
					query,
				] ),
			};
		},
		[ searchQuery ],
		500
	);

	/**
	 * Filters the title for the item list in the Student Modal.
	 *
	 * @since 4.8.0
	 *
	 * @param {string} title  Title text.
	 * @param {string} action Current action for the Student Modal.
	 *
	 * @return {string} Filtered title text.
	 */
	const title = applyFilters(
		'senseiStudentModalItemListTitle',
		__( 'Your Courses', 'sensei-lms' ),
		action
	);

	return (
		<>
			<span className="sensei-student-modal__course-list__header">
				{ title }
			</span>
			<ul className="sensei-student-modal__course-list">
				{ isFetching && <LoadingItemList /> }

				{ ! isFetching && 0 === items.length && <EmptyItemList /> }

				{ ! isFetching &&
					0 < items.length &&
					items.map( ( item ) => (
						<Item
							key={ item.id }
							item={ item }
							onChange={ selectItem }
							checked={
								selectedItems.current.length > 0 &&
								selectedItems.current.find(
									( { id } ) => id === item.id
								)
							}
						/>
					) ) }
			</ul>
		</>
	);
};

export default ItemList;
