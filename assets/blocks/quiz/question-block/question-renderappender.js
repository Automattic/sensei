/**
 * WordPress dependencies
 */
import { createBlock } from '@wordpress/blocks';
import { Button } from '@wordpress/components';
import { select, withSelect, withDispatch } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';


/**
 * Custom Appender meant to be used when there is only one type of block that can be inserted to an InnerBlocks instance.
 *
 * @param buttonText
 * @param onClick
 * @param clientId
 * @param allowedBlock
 * @param innerBlocks
 * @param {Object} props
 */
const QuestionBlockAppender = ( { buttonText = __( 'Add Item' ), onClick, clientId, allowedBlocks, allowedBlock, innerBlocks, ...props } ) => {
  const insertableBlocks = [];
  allowedBlocks.map( ( allowedBlockName ) => {
    if ( innerBlocks.map( theBlock => theBlock.name ).indexOf( allowedBlockName ) === -1 ) {
      insertableBlocks.push ( allowedBlockName );
    }
  });
  console.log(insertableBlocks)

  return(
    <>
      <Button
        onClick={ onClick }
        { ...props}
      >
        {buttonText}
      </Button>
    </>
  );
};


export default compose( [
    withSelect( ( select, ownProps ) => {
        return {
            innerBlocks: select( 'core/block-editor' ).getBlock( ownProps.clientId ).innerBlocks
        };
    } ),
    withDispatch( ( dispatch, ownProps ) => {
        return {
            onClick() {
                const newBlock = createBlock( ownProps.allowedBlock );
                dispatch( 'core/block-editor' ).insertBlock( newBlock, ownProps.innerBlocks.length, ownProps.clientId );
            }
        };
    } )
] )( QuestionBlockAppender );