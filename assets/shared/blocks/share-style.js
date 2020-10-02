import { BaseControl, Button } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { pick } from 'lodash';

/**
 * Add a control to apply color and style settings to all of the same blocks.
 *
 * @param {Object}   props
 * @param {string}   props.clientId             Current block ID.
 * @param {Object}   props.attributes           Current block attributes.
 * @param {string[]} props.sharedAttributeNames Attributes to share between modules.
 * @param {string}   props.help                 Help text.
 * @param {string}   props.label                Button label text.
 */
export const ShareStyle = ( {
	clientId,
	attributes,
	sharedAttributeNames,
	help,
	label,
} ) => {
	const getModuleBlocks = useSelect(
		( select ) => {
			return () => {
				const blockEditor = select( 'core/block-editor' );
				const outlineBlock = blockEditor.getBlockParentsByBlockName(
					clientId,
					'sensei-lms/course-outline'
				);

				let blocks = blockEditor.getBlocks( outlineBlock[ 0 ] );
				blocks = blocks.reduce(
					( m, block ) => [ ...m, ...block.innerBlocks ],
					blocks
				);

				const blockType = blockEditor.getBlock( clientId ).name;

				if ( ! outlineBlock.length ) return [];

				return blocks.filter( ( { name } ) => blockType === name );
			};
		},
		[ clientId ]
	);

	const { updateBlockAttributes } = useDispatch( 'core/block-editor' );

	const applyStyle = () => {
		updateBlockAttributes(
			getModuleBlocks().map( ( block ) => block.clientId ),
			pick( attributes, sharedAttributeNames )
		);
	};

	return (
		<>
			<BaseControl help={ help }>
				<Button isLink onClick={ applyStyle }>
					{ label }
				</Button>
			</BaseControl>
		</>
	);
};
