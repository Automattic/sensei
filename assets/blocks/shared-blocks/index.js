/**
 * Internal dependencies
 */
import registerSenseiBlocks from '../register-sensei-blocks';
import ContactTeacherBlock from './contact-teacher-block';
import RestrictedContentBlock from './restricted-content-block';

registerSenseiBlocks( [ ContactTeacherBlock, RestrictedContentBlock ] );
