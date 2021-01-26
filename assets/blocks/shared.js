/**
 * Internal dependencies
 */
import registerSenseiBlocks from './register-sensei-blocks';
import ContactTeacherBlock from './shared-blocks/contact-teacher-block';
import RestrictedContentBlock from './shared-blocks/restricted-content-block';

registerSenseiBlocks( [ ContactTeacherBlock, RestrictedContentBlock ] );
