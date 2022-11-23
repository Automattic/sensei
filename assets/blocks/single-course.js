/**
 * Internal dependencies
 */
import registerSenseiBlocks from './register-sensei-blocks';
import { OutlineBlock, LessonBlock, ModuleBlock } from './course-outline';

registerSenseiBlocks( [ OutlineBlock, ModuleBlock, LessonBlock ] );
