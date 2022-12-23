/**
 * WordPress dependencies
 */
import { useInnerBlocksProps, useBlockProps } from '@wordpress/block-editor';
import type { WPSyntheticEvent } from '@wordpress/element';

export type Props = {
	context: {
		selected: string | null;
		setSelection: ( selected: string ) => void;
	};
	clientId: string;
	attributes: { isOpen: boolean };
};

export const Details = ( props: Props ): JSX.Element => {
	const { attributes, context, clientId } = props;
	const { selected, setSelection } = context;
	const { isOpen } = attributes;

	const blockProps = useBlockProps();
	const innerBlockProps = useInnerBlocksProps( blockProps, {
		template: [
			[ 'sensei-lms/accordion-summary', {} ],
			[ 'sensei-lms/accordion-content', {} ],
		],
	} );

	const toggleAll = ( e: WPSyntheticEvent ) => {
		const { open } = e.target as HTMLDetailsElement;
		if ( open ) {
			setSelection( clientId );
		}
	};

	return (
		<>
			<details
				open={ selected === clientId || isOpen }
				onToggle={ toggleAll }
				{ ...innerBlockProps }
			></details>
		</>
	);
};

export default Details;
