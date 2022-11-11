<?php
/**
 * File containing the class Sensei_Autoloader.
 *
 * @package sensei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // security check, don't load file outside WP.
}

/**
 * Loading all class files within the Sensei/includes directory
 *
 * The auto loader class listens for calls to classes within Sensei and loads
 * the file containing the class.
 *
 * @package Core
 * @since 1.9.0
 */
class Sensei_Autoloader {

	/**
	 * Path to the includes directory within Sensei.
	 *
	 * @var string
	 */
	private $include_path = 'includes';

	/**
	 * List of classes mapped to their files.
	 *
	 * @var array
	 */
	private $class_file_map;

	/**
	 * An array of bundle directories to be loaded.
	 *
	 * @var array
	 */
	private $autoloader_bundles;

	/**
	 * Constructor
	 *
	 * @since 1.9.0
	 */
	public function __construct() {

		// Make sure we do not override an existing autoload function.
		if ( function_exists( '__autoload' ) ) {
			spl_autoload_register( '__autoload' );
		}

		// Setup a relative path for the current autoload instance.
		$this->include_path = trailingslashit( untrailingslashit( __DIR__ ) );

		// Setup the class file map.
		$this->initialize_class_file_map();

		require_once __DIR__ . '/class-sensei-autoloader-bundle.php';

		$this->autoloader_bundles = array(
			new Sensei_Autoloader_Bundle( 'rest-api' ),
			new Sensei_Autoloader_Bundle( 'rest-api/mappers' ),
			new Sensei_Autoloader_Bundle( '' ),
			new Sensei_Autoloader_Bundle( 'background-jobs' ),
			new Sensei_Autoloader_Bundle( 'enrolment' ),
			new Sensei_Autoloader_Bundle( 'data-port' ),
			new Sensei_Autoloader_Bundle( 'data-port/import-tasks' ),
			new Sensei_Autoloader_Bundle( 'data-port/export-tasks' ),
			new Sensei_Autoloader_Bundle( 'data-port/models' ),
			new Sensei_Autoloader_Bundle( 'blocks' ),
			new Sensei_Autoloader_Bundle( 'blocks/course-list' ),
			new Sensei_Autoloader_Bundle( 'course-theme' ),
			new Sensei_Autoloader_Bundle( 'course-video' ),
			new Sensei_Autoloader_Bundle( 'course-video/blocks' ),
			new Sensei_Autoloader_Bundle( 'reports/helper' ),
			new Sensei_Autoloader_Bundle( 'reports/overview/data-provider' ),
			new Sensei_Autoloader_Bundle( 'reports/overview/list-table' ),
			new Sensei_Autoloader_Bundle( 'reports/overview/services' ),
			new Sensei_Autoloader_Bundle( 'admin/home' ),
			new Sensei_Autoloader_Bundle( 'admin/home/notices' ),
			new Sensei_Autoloader_Bundle( 'admin/home/quick-links' ),
			new Sensei_Autoloader_Bundle( 'admin/home/help' ),
			new Sensei_Autoloader_Bundle( 'admin/home/promo-banner' ),
			new Sensei_Autoloader_Bundle( 'admin/home/tasks' ),
			new Sensei_Autoloader_Bundle( 'admin/home/tasks/task' ),
		);

		// Add Sensei custom auto loader.
		spl_autoload_register( array( $this, 'autoload' ) );

	}

	/**
	 * Generate a list of Sensei class and map them the their respective
	 * files within the includes directory
	 *
	 * @since 1.9.0
	 */
	public function initialize_class_file_map() {

		$this->class_file_map = array(

			/**
			 * Main Sensei class
			 */
			'Sensei_Main'                                  => 'class-sensei.php',

			/**
			 * Emails
			 */
			'Sensei_Email_Learner_Completed_Course'        => 'emails/class-sensei-email-learner-completed-course.php',
			'Sensei_Email_Learner_Graded_Quiz'             => 'emails/class-sensei-email-learner-graded-quiz.php',
			'Sensei_Email_New_Message_Reply'               => 'emails/class-sensei-email-new-message-reply.php',
			'Sensei_Email_Teacher_Completed_Course'        => 'emails/class-sensei-email-teacher-completed-course.php',
			'Sensei_Email_Teacher_Completed_Lesson'        => 'emails/class-sensei-email-teacher-completed-lesson.php',
			'Sensei_Email_Teacher_New_Course_Assignment'   => 'emails/class-sensei-email-teacher-new-course-assignment.php',
			'Sensei_Email_Teacher_New_Message'             => 'emails/class-sensei-email-teacher-new-message.php',
			'Sensei_Email_Teacher_Quiz_Submitted'          => 'emails/class-sensei-email-teacher-quiz-submitted.php',
			'Sensei_Email_Teacher_Started_Course'          => 'emails/class-sensei-email-teacher-started-course.php',

			/**
			 * Admin
			 */
			'Sensei_Learner_Management'                    => 'admin/class-sensei-learner-management.php',
			'Sensei_Extensions'                            => 'admin/class-sensei-extensions.php',
			'Sensei_Exit_Survey'                           => 'admin/class-sensei-exit-survey.php',
			'Sensei_Home'                                  => 'admin/class-sensei-home.php',
			'Sensei_Learners_Admin_Bulk_Actions_Controller' => 'admin/class-sensei-learners-admin-bulk-actions-controller.php',
			'Sensei_Learners_Admin_Bulk_Actions_View'      => 'admin/class-sensei-learners-admin-bulk-actions-view.php',
			'Sensei_Learners_Main'                         => 'admin/class-sensei-learners-main.php',
			'Sensei_Email_Signup_Form'                     => 'email-signup/class-sensei-email-signup-form.php', // @deprecated 3.1.0
			'Sensei_Setup_Wizard'                          => 'admin/class-sensei-setup-wizard.php',
			'Sensei_Setup_Wizard_Pages'                    => 'admin/class-sensei-setup-wizard-pages.php',
			'Sensei_Plugins_Installation'                  => 'admin/class-sensei-plugins-installation.php',
			'Sensei_Status'                                => 'admin/class-sensei-status.php',
			'Sensei_Admin_Notices'                         => 'admin/class-sensei-admin-notices.php',
			'Sensei_Editor_Wizard'                         => 'admin/class-sensei-editor-wizard.php',
			'Sensei_No_Users_Table_Relationship'           => 'reports/class-sensei-no-users-table-relationship.php',

			/**
			 * Admin Tools
			 */
			'Sensei_Tools'                                 => 'admin/class-sensei-tools.php',
			'Sensei_Tool_Interface'                        => 'admin/tools/class-sensei-tool-interface.php',
			'Sensei_Tool_Interactive_Interface'            => 'admin/tools/class-sensei-tool-interactive-interface.php',
			'Sensei_Tool_Recalculate_Course_Enrolment'     => 'admin/tools/class-sensei-tool-recalculate-course-enrolment.php',
			'Sensei_Tool_Recalculate_Enrolment'            => 'admin/tools/class-sensei-tool-recalculate-enrolment.php',
			'Sensei_Tool_Ensure_Roles'                     => 'admin/tools/class-sensei-tool-ensure-roles.php',
			'Sensei_Tool_Remove_Deleted_User_Data'         => 'admin/tools/class-sensei-tool-remove-deleted-user-data.php',
			'Sensei_Tool_Module_Slugs_Mismatch'            => 'admin/tools/class-sensei-tool-module-slugs-mismatch.php',
			'Sensei_Tool_Enrolment_Debug'                  => 'admin/tools/class-sensei-tool-enrolment-debug.php',
			'Sensei_Tool_Import'                           => 'admin/tools/class-sensei-tool-import.php',
			'Sensei_Tool_Export'                           => 'admin/tools/class-sensei-tool-export.php',

			/**
			 * Shortcodes
			 */
			'Sensei_Shortcode_Loader'                      => 'shortcodes/class-sensei-shortcode-loader.php',
			'Sensei_Shortcode_Interface'                   => 'shortcodes/interface-sensei-shortcode.php',
			'Sensei_Shortcode_Featured_Courses'            => 'shortcodes/class-sensei-shortcode-featured-courses.php',
			'Sensei_Shortcode_User_Courses'                => 'shortcodes/class-sensei-shortcode-user-courses.php',
			'Sensei_Shortcode_Courses'                     => 'shortcodes/class-sensei-shortcode-courses.php',
			'Sensei_Shortcode_Teachers'                    => 'shortcodes/class-sensei-shortcode-teachers.php',
			'Sensei_Shortcode_User_Messages'               => 'shortcodes/class-sensei-shortcode-user-messages.php',
			'Sensei_Shortcode_Course_Page'                 => 'shortcodes/class-sensei-shortcode-course-page.php',
			'Sensei_Shortcode_Lesson_Page'                 => 'shortcodes/class-sensei-shortcode-lesson-page.php',
			'Sensei_Shortcode_Course_Categories'           => 'shortcodes/class-sensei-shortcode-course-categories.php',
			'Sensei_Shortcode_Unpurchased_Courses'         => 'shortcodes/class-sensei-shortcode-unpurchased-courses.php',

			/**
			 * Renderers
			 */
			'Sensei_Renderer_Interface'                    => 'renderers/interface-sensei-renderer.php',
			'Sensei_Renderer_Single_Post'                  => 'renderers/class-sensei-renderer-single-post.php',

			/**
			 * Update tasks.
			 */
			'Sensei_Update_Fix_Question_Author'            => 'update-tasks/class-sensei-update-fix-question-author.php',
			'Sensei_Update_Remove_Abandoned_Multiple_Question' => 'update-tasks/class-sensei-update-remove-abandoned-multiple-question.php',

			/**
			 * Unsupported theme handlers.
			 */
			'Sensei_Unsupported_Theme_Handler_Interface'   => 'unsupported-theme-handlers/interface-sensei-unsupported-theme-handler.php',
			'Sensei_Unsupported_Theme_Handler_Page_Imitator' => 'unsupported-theme-handlers/class-sensei-unsupported-theme-handler-page-imitator.php',
			'Sensei_Unsupported_Theme_Handler_Utils'       => 'unsupported-theme-handlers/class-sensei-unsupported-theme-handler-utils.php',
			'Sensei_Unsupported_Theme_Handler_CPT'         => 'unsupported-theme-handlers/class-sensei-unsupported-theme-handler-cpt.php',
			'Sensei_Unsupported_Theme_Handler_Module'      => 'unsupported-theme-handlers/class-sensei-unsupported-theme-handler-module.php',
			'Sensei_Unsupported_Theme_Handler_Course_Results' => 'unsupported-theme-handlers/class-sensei-unsupported-theme-handler-course-results.php',
			'Sensei_Unsupported_Theme_Handler_Lesson_Tag_Archive' => 'unsupported-theme-handlers/class-sensei-unsupported-theme-handler-lesson-tag-archive.php',
			'Sensei_Unsupported_Theme_Handler_Teacher_Archive' => 'unsupported-theme-handlers/class-sensei-unsupported-theme-handler-teacher-archive.php',
			'Sensei_Unsupported_Theme_Handler_Message_Archive' => 'unsupported-theme-handlers/class-sensei-unsupported-theme-handler-message-archive.php',
			'Sensei_Unsupported_Theme_Handler_Learner_Profile' => 'unsupported-theme-handlers/class-sensei-unsupported-theme-handler-learner-profile.php',
			'Sensei_Unsupported_Theme_Handler_Course_Archive' => 'unsupported-theme-handlers/class-sensei-unsupported-theme-handler-course-archive.php',

			/**
			 * Built in theme integration support
			 */
			'Sensei_Theme_Integration_Loader'              => 'theme-integrations/theme-integration-loader.php',
			'Sensei__S'                                    => 'theme-integrations/underscores.php',
			'Sensei_Twentyeleven'                          => 'theme-integrations/twentyeleven.php',
			'Sensei_Twentytwelve'                          => 'theme-integrations/twentytwelve.php',
			'Sensei_Twentythirteen'                        => 'theme-integrations/Twentythirteen.php',
			'Sensei_Twentyfourteen'                        => 'theme-integrations/Twentyfourteen.php',
			'Sensei_Twentyfifteen'                         => 'theme-integrations/Twentyfifteen.php',
			'Sensei_Twentysixteen'                         => 'theme-integrations/Twentysixteen.php',
			'Sensei_Storefront'                            => 'theme-integrations/Storefront.php',

			/**
			 * WPML
			 */
			'Sensei_WPML'                                  => 'wpml/class-sensei-wpml.php',

			/**
			 * Blocks
			 */
			'Sensei\Blocks\Shared\Progress_Bar'            => 'blocks/shared/class-progress-bar.php',

			/**
			 * Block patterns.
			 */
			'Sensei_Block_Patterns'                        => 'block-patterns/class-sensei-block-patterns.php',

			/**
			 * Course Theme
			 */
			'Sensei\Blocks\Course_Theme_Blocks'            => 'blocks/course-theme/class-course-theme-blocks.php',
			'Sensei\Blocks\Course_Theme\Prev_Next_Lesson'  => 'blocks/course-theme/class-prev-next-lesson.php',
			'Sensei\Blocks\Course_Theme\Exit_Course'       => 'blocks/course-theme/class-exit-course.php',
			'Sensei\Blocks\Course_Theme\Course_Title'      => 'blocks/course-theme/class-course-title.php',
			'Sensei\Blocks\Course_Theme\Course_Navigation' => 'blocks/course-theme/class-course-navigation.php',
			'Sensei\Blocks\Course_Theme\Site_Logo'         => 'blocks/course-theme/class-site-logo.php',
			'Sensei\Blocks\Course_Theme\Focus_Mode'        => 'blocks/course-theme/class-focus-mode.php',
			'Sensei\Blocks\Course_Theme\Notices'           => 'blocks/course-theme/class-notices.php',
			'Sensei\Blocks\Course_Theme\Lesson_Actions'    => 'blocks/course-theme/class-lesson-actions.php',
			'Sensei\Blocks\Course_Theme\Quiz_Back_To_Lesson' => 'blocks/course-theme/class-quiz-back-to-lesson.php',
			'Sensei\Blocks\Course_Theme\Course_Progress_Counter' => 'blocks/course-theme/class-course-progress-counter.php',
			'Sensei\Blocks\Course_Theme\Course_Progress_Bar' => 'blocks/course-theme/class-course-progress-bar.php',
			'Sensei\Blocks\Course_Theme\Course_Content'    => 'blocks/course-theme/class-course-content.php',
			'Sensei\Blocks\Course_Theme\Quiz_Content'      => 'blocks/course-theme/class-quiz-content.php',
			'Sensei\Blocks\Course_Theme\Post_Title'        => 'blocks/course-theme/class-post-title.php',
			'Sensei\Blocks\Course_Theme\Lesson_Module'     => 'blocks/course-theme/class-lesson-module.php',
			'Sensei\Blocks\Course_Theme\Lesson_Properties' => 'blocks/course-theme/class-lesson-properties.php',
			'Sensei\Blocks\Course_Theme\Sidebar_Toggle_Button' => 'blocks/course-theme/class-sidebar-toggle-button.php',
			'Sensei\Blocks\Course_Theme\Quiz_Graded'       => 'blocks/course-theme/class-quiz-graded.php',
			'Sensei\Blocks\Course_Theme\Quiz_Actions'      => 'blocks/course-theme/class-quiz-actions.php',
			'Sensei\Blocks\Course_Theme\Page_Actions'      => 'blocks/course-theme/class-page-actions.php',
			'Sensei\Blocks\Course_Theme\Ui'                => 'blocks/course-theme/class-ui.php',
			'Sensei\Blocks\Course_Theme\Template_Style'    => 'blocks/course-theme/class-template-style.php',
			'Sensei\Blocks\Course_Theme\Lesson_Video'      => 'blocks/course-theme/class-lesson-video.php',

			/**
			 * Student Progress
			 */
			\Sensei\Internal\Student_Progress\Course_Progress\Models\Course_Progress::class => 'internal/student-progress/course-progress/models/class-course-progress.php',
			\Sensei\Internal\Student_Progress\Lesson_Progress\Models\Lesson_Progress::class => 'internal/student-progress/lesson-progress/models/class-lesson-progress.php',
			\Sensei\Internal\Student_Progress\Quiz_Progress\Models\Quiz_Progress::class => 'internal/student-progress/quiz-progress/models/class-quiz-progress.php',
			\Sensei\Internal\Student_Progress\Course_Progress\Repositories\Course_Progress_Repository_Interface::class => 'internal/student-progress/course-progress/repositories/class-course-progress-repository-interface.php',
			\Sensei\Internal\Student_Progress\Course_Progress\Repositories\Comments_Based_Course_Progress_Repository::class => 'internal/student-progress/course-progress/repositories/class-comments-based-course-progress-repository.php',
			\Sensei\Internal\Student_Progress\Course_Progress\Repositories\Course_Progress_Repository_Factory::class => 'internal/student-progress/course-progress/repositories/class-course-progress-repository-factory.php',
			\Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Lesson_Progress_Repository_Interface::class => 'internal/student-progress/lesson-progress/repositories/class-lesson-progress-repository-interface.php',
			\Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Comments_Based_Lesson_Progress_Repository::class => 'internal/student-progress/lesson-progress/repositories/class-comments-based-lesson-progress-repository.php',
			\Sensei\Internal\Student_Progress\Lesson_Progress\Repositories\Lesson_Progress_Repository_Factory::class => 'internal/student-progress/lesson-progress/repositories/class-lesson-progress-repository-factory.php',
			\Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Quiz_Progress_Repository_Interface::class => 'internal/student-progress/quiz-progress/repositories/class-quiz-progress-repository-interface.php',
			\Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Comments_Based_Quiz_Progress_Repository::class => 'internal/student-progress/quiz-progress/repositories/class-comments-based-quiz-progress-repository.php',
			\Sensei\Internal\Student_Progress\Quiz_Progress\Repositories\Quiz_Progress_Repository_Factory::class => 'internal/student-progress/quiz-progress/repositories/class-quiz-progress-repository-factory.php',

			/**
			 * Quiz Submission
			 */
			\Sensei\Internal\Quiz_Submission\Answer\Models\Answer::class => 'internal/quiz-submission/answer/models/class-answer.php',
			\Sensei\Internal\Quiz_Submission\Answer\Repositories\Comments_Based_Answer_Repository::class => 'internal/quiz-submission/answer/repositories/class-comments-based-answer-repository.php',
			\Sensei\Internal\Quiz_Submission\Answer\Repositories\Answer_Repository_Factory::class => 'internal/quiz-submission/answer/repositories/class-answer-repository-factory.php',
			\Sensei\Internal\Quiz_Submission\Answer\Repositories\Answer_Repository_Interface::class => 'internal/quiz-submission/answer/repositories/class-answer-repository-interface.php',
			\Sensei\Internal\Quiz_Submission\Grade\Models\Grade::class => 'internal/quiz-submission/grade/models/class-grade.php',
			\Sensei\Internal\Quiz_Submission\Grade\Repositories\Comments_Based_Grade_Repository::class => 'internal/quiz-submission/grade/repositories/class-comments-based-grade-repository.php',
			\Sensei\Internal\Quiz_Submission\Grade\Repositories\Grade_Repository_Factory::class => 'internal/quiz-submission/grade/repositories/class-grade-repository-factory.php',
			\Sensei\Internal\Quiz_Submission\Grade\Repositories\Grade_Repository_Interface::class => 'internal/quiz-submission/grade/repositories/class-grade-repository-interface.php',
			\Sensei\Internal\Quiz_Submission\Submission\Models\Submission::class => 'internal/quiz-submission/submission/models/class-submission.php',
			\Sensei\Internal\Quiz_Submission\Submission\Repositories\Comments_Based_Submission_Repository::class => 'internal/quiz-submission/submission/repositories/class-comments-based-submission-repository.php',
			\Sensei\Internal\Quiz_Submission\Submission\Repositories\Submission_Repository_Factory::class => 'internal/quiz-submission/submission/repositories/class-submission-repository-factory.php',
			\Sensei\Internal\Quiz_Submission\Submission\Repositories\Submission_Repository_Interface::class => 'internal/quiz-submission/submission/repositories/class-submission-repository-interface.php',
		);
	}

	/**
	 * Autoload all sensei files as the class names are used.
	 *
	 * @param string $class The class name.
	 */
	public function autoload( $class ) {

		// Only handle classes with the word `sensei` in it.
		if ( ! is_numeric( strpos( strtolower( $class ), 'sensei' ) ) ) {

			return;

		}

		// Exit if we didn't provide mapping for this class.
		if ( isset( $this->class_file_map[ $class ] ) ) {

			$file_location = $this->include_path . $this->class_file_map[ $class ];
			require_once $file_location;
			return;

		}

		foreach ( $this->autoloader_bundles as $bundle ) {
			if ( true === $bundle->load_class( $class ) ) {
				return;
			}
		}
	}
}
