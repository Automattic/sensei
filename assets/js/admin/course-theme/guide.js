/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Modal } from '@wordpress/components';

/**
 * This component is an adaptation of Guide component from Gutenberg.
 * It was adapted mainly to allow an action when closing the onboarding,
 * and different ones when clicking on the buttons.
 *
 * @link https://github.com/WordPress/gutenberg/tree/trunk/packages/components/src/guide
 */
export default function Guide( {
	className,
	contentLabel,
	onFinish,
	pages = [],
} ) {
	const [ currentPage, setCurrentPage ] = useState( 0 );

	const canGoBack = currentPage > 0;
	const canGoForward = currentPage < pages.length - 1;

	const goBack = () => {
		if ( canGoBack ) {
			setCurrentPage( currentPage - 1 );
		}
	};

	const goForward = () => {
		if ( canGoForward ) {
			setCurrentPage( currentPage + 1 );
		}
	};

	if ( pages.length === 0 ) {
		return null;
	}

	return (
		<Modal
			className={ classnames( 'components-guide', className ) }
			contentLabel={ contentLabel }
			onRequestClose={ onFinish }
		>
			<div className="components-guide__container">
				<div className="components-guide__page">
					{ pages[ currentPage ].image }

					{ pages[ currentPage ].content }
				</div>

				<div className="components-guide__footer">
					{ pages[ currentPage ].footer( {
						canGoBack,
						canGoForward,
						goBack,
						goForward,
						onFinish,
					} ) }
				</div>
			</div>
		</Modal>
	);
}
