/**
 * WordPress dependencies
 */
import { createContext } from '@wordpress/element';

/**
 * A React context which contains the course status and a function to set it.
 *
 * It should contain the following properties:
 *
 * - courseStatus: The course status.
 * - setCourseStatus: A function to set the course status.
 */
const CourseStatusContext = createContext();

export default CourseStatusContext;
