/**
 * WordPress dependencies
 */
import {
	InnerBlocks,
	store as blockEditorStore,
	useBlockProps,
} from '@wordpress/block-editor';
import { useDispatch, useSelect } from '@wordpress/data';
import {
	createContext,
	useCallback,
	useEffect,
	useState,
} from '@wordpress/element';

/**
 * Internal dependencies
 */
import OutlineSettings from './outline-settings';
import { useCourseLessonsStatusSync } from '../status-preview/use-course-lessons-status-sync';
import { COURSE_STORE } from '../course-outline-store';
import { useBlocksCreator } from '../use-block-creator';
import OutlineAppender from './outline-appender';
import ExistingLessonsModal from './existing-lessons-modal';
import OutlinePlaceholder from './outline-placeholder';
import useSenseiProSettings from './use-sensei-pro-settings';
import { applyFilters } from '@wordpress/hooks';
import { getSenseiProUpsellUrl } from '../../../admin/helpers';

const ALLOWED_BLOCKS = [
	'sensei-lms/course-outline-module',
	'sensei-lms/course-outline-lesson',
];

/**
 * A React context which contains the attributes and the setAttributes callback of the Outline block.
 */
export const OutlineAttributesContext = createContext();

/**
 * Edit course outline block component.
 *
 * @param {Object}   props               Component props.
 * @param {string}   props.clientId      Block client ID.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Block setAttributes callback.
 */
const OutlineEdit = ( props ) => {
	const { clientId, attributes, setAttributes } = props;

	const blockProps = useBlockProps( {
		className:
			attributes.className && attributes.className.includes( 'is-style-' )
				? undefined
				: 'is-style-default',
	} );

	const { loadStructure } = useDispatch( COURSE_STORE );

	const { isActivated: isSenseiProActivated } = useSenseiProSettings();

	/**
	 * Filters if the course outline generator upsell should be removed or not.
	 *
	 * @since 4.17.0
	 *
	 * @param {boolean} removeCourseOutlineGeneratorUpsell Whether to remove the course outline generator upsell.
	 * @return {boolean} Whether to remove the course outline generator upsell.
	 */
	const removeCourseOutlineGeneratorUpsell = applyFilters(
		'senseiCourseOutlineGeneratorUpsellRemove',
		isSenseiProActivated
	);

	useEffect( () => {
		if ( ! attributes.isPreview ) {
			loadStructure();
		}
	}, [ attributes.isPreview, loadStructure ] );

	const { setBlocks } = useBlocksCreator( clientId );

	const { isEmpty } = useSelect(
		( select ) => ( {
			isEmpty: ! select( blockEditorStore ).getBlocks( clientId ).length,
		} ),
		[ clientId ]
	);

	useCourseLessonsStatusSync( clientId, attributes.isPreview );

	const [ isExistingLessonsModalOpen, setExistingLessonsModalOpen ] =
		useState( false );

	const closeExistingLessonsModal = () =>
		setExistingLessonsModalOpen( false );

	const AppenderComponent = useCallback(
		() => (
			<OutlineAppender
				clientId={ clientId }
				openModal={ () => setExistingLessonsModalOpen( true ) }
			/>
		),
		[ clientId ]
	);

	const openTailoredModal = useCallback( () => {
		if ( removeCourseOutlineGeneratorUpsell ) {
			window.location.hash = 'generate-course-outline-using-ai';
		} else {
			window.open(
				getSenseiProUpsellUrl( 'outline_edit' ),
				'_blank',
				'noopener'
			);
		}
	}, [ removeCourseOutlineGeneratorUpsell ] );

	return (
		<div { ...blockProps }>
			{ isEmpty ? (
				<OutlinePlaceholder
					addBlock={ ( type ) => setBlocks( [ { type } ], true ) }
					addBlocks={ setBlocks }
					openTailoredModal={ openTailoredModal }
				/>
			) : (
				<OutlineAttributesContext.Provider
					value={ {
						outlineAttributes: attributes,
						outlineSetAttributes: setAttributes,
						outlineClassName: blockProps.className,
					} }
				>
					<OutlineSettings { ...props } />

					<section>
						<InnerBlocks
							allowedBlocks={ ALLOWED_BLOCKS }
							renderAppender={ AppenderComponent }
						/>
					</section>
				</OutlineAttributesContext.Provider>
			) }
			{ isExistingLessonsModalOpen && (
				<ExistingLessonsModal
					clientId={ clientId }
					onClose={ closeExistingLessonsModal }
				/>
			) }
		</div>
	);
};

export default OutlineEdit;
