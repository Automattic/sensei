/**
 * WordPress dependencies
 */
import {
	InnerBlocks,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { compose } from '@wordpress/compose';
import { useDispatch, useSelect } from '@wordpress/data';
import { createContext, useCallback, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import OutlineSettings from './outline-settings';
import { withDefaultBlockStyle } from '../../../shared/blocks/settings';
import { useCourseLessonsStatusSync } from '../status-preview/use-course-lessons-status-sync';
import { COURSE_STORE } from '../course-outline-store';
import { useBlocksCreator } from '../use-block-creator';
import OutlineAppender from './outline-appender';
import OutlinePlaceholder from './outline-placeholder';
import useSenseiProSettings from './use-sensei-pro-settings';

const SENSEI_PRO_LINK = 'https://senseilms.com/sensei-pro/';

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
 * @param {string}   props.className     Custom class name.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Block setAttributes callback.
 */
const OutlineEdit = ( props ) => {
	const { clientId, className, attributes, setAttributes } = props;

	const { loadStructure } = useDispatch( COURSE_STORE );

	const { isActivated: isSenseiProActivated } = useSenseiProSettings();

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

	const AppenderComponent = useCallback(
		() => <OutlineAppender clientId={ clientId } />,
		[ clientId ]
	);

	const openTailoredModal = useCallback( () => {
		if ( isSenseiProActivated ) {
			window.location.hash = 'generate-course-outline-using-ai';
		} else {
			window.location.href = SENSEI_PRO_LINK;
		}
	}, [ isSenseiProActivated ] );

	return isEmpty ? (
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
				outlineClassName: className,
			} }
		>
			<OutlineSettings { ...props } />

			<section className={ className }>
				<InnerBlocks
					allowedBlocks={ ALLOWED_BLOCKS }
					renderAppender={ AppenderComponent }
				/>
			</section>
		</OutlineAttributesContext.Provider>
	);
};

export default compose( withDefaultBlockStyle() )( OutlineEdit );
