<?php
/**
 * The Template for displaying all Quiz Questions.
 *
 * Override this template by copying it to yourtheme/sensei/single-quiz.php
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>

<?php get_header();  ?>

<?php

    /**
     * sensei_before_main_content hook
     *
     * @hooked sensei_output_content_wrapper - 10 (outputs opening divs for the content)
     */
    do_action( 'sensei_before_main_content' );

?>

<article <?php post_class(); ?>>

    <?php

        /**
         * Hook inside the single quiz post above the content
         *
         * @since 1.9.0
         *
         * @hooked
         * @param $quiz_id
         *
         */
        do_action( 'sensei_single_quiz_content_inside_before', get_the_ID() );

    ?>

    <header>

        <h2 class="title"> <? the_title(); ?> </h2>

    </header>

    <section class="entry quiz-questions">

        <?php the_content(); // @todo why would quiz content be needed? The quiz post type is only a list ofQ's check it out?>

        <?php  // Handle Quiz Completion @todo deprecate
        do_action( 'sensei_complete_quiz' ); ?>

        <?php if ( sensei_quiz_has_questions() ): ?>

            <form method="POST" action="<?php echo esc_url_raw( get_permalink() ); ?>" enctype="multipart/form-data">

                <?php

                    /**
                     * Action inside before the question content on single-quiz page
                     *
                     * @hooked WooThemes_Sensei_Quiz::the_user_status_message  - 10
                     *
                     * @param string $the_quiz_id
                     */
                    do_action( 'sensei_single_quiz_questions_before', get_the_id() );

                ?>



                <ol id="sensei-quiz-list">

                <?php while ( sensei_quiz_has_questions() ): sensei_setup_the_question(); ?>

                    <li class="<?php sensei_the_question_class();?>">

                        <?php

                            /**
                             * Action inside before the question content on single-quiz page
                             *
                             * @hooked WooThemes_Sensei_Question::the_question_title        - 10
                             * @hooked WooThemes_Sensei_Question::the_question_description  - 20
                             * @hooked WooThemes_Sensei_Question::the_question_media        - 30
                             * @hooked WooThemes_Sensei_Question::the_question_hidden_field - 40
                             *
                             * @since 1.9.0
                             * @param string $the_question_id
                             */
                            do_action( 'sensei_quiz_question_inside_before', sensei_get_the_question_id() );

                        ?>

                        <?php sensei_the_question_content(); ?>

                        <?php

                            /**
                             * Action inside before the question content on single-quiz page
                             *
                             * @hooked WooThemes_Sensei_Question::answer_feedback_notes
                             *
                             * @param string $the_question_id
                             */
                            do_action( 'sensei_quiz_question_inside_after', sensei_get_the_question_id() );

                        ?>

                        <?php    // @todo deprecate
                        // Question Type
                        global $sensei_question_loop;
                        $question_type = $woothemes_sensei->question->get_question_type( $sensei_question_loop['current_question']->ID );
                        do_action( 'sensei_quiz_question_type', $question_type );
                        ?>

                    </li>

                <?php endwhile; ?>

                </ol>

                <?php

                    /**
                     * Action inside before the question content on single-quiz page
                     *
                     * @param string $the_quiz_id
                     */
                    do_action( 'sensei_single_quiz_questions_after', get_the_id() );

                ?>

            </form>
        <?php else:  ?>

            <div class="sensei-message alert"> <?php _e( 'There are no questions for this Quiz yet. Check back soon.', 'woothemes-sensei' ); ?></div>

        <?php endif; // End If have questions ?>


        <?php
            $quiz_lesson = $woothemes_sensei->quiz->data->quiz_lesson;
            do_action( 'sensei_quiz_back_link', $quiz_lesson  );
        ?>

    </section>

    <?php

    /**
     * Hook inside the single quiz post above the content
     *
     * @since 1.9.0
     *
     * @hooked
     *
     */
    do_action( 'sensei_single_quiz_content_inside_after' );

    ?>

</article><!-- .quiz -->

<?php
/**
 *
 */
    do_action('sensei_pagination');

?>

<?php

    /**
     * sensei_breadcrumb hook
     *
     * @hooked sensei_breadcrumb - 10 (outputs sensei breadcrumb trail)
     */
    do_action( 'sensei_breadcrumb', $post->ID );

?>

<?php

    /**
     * sensei_after_main_content hook
     *
     * @hooked sensei_output_content_wrapper_end - 10 (outputs closing divs for the content)
     */
    do_action( 'sensei_after_main_content' );

?>

<?php

    /**
     * sensei_sidebar hook
     *
     * @hooked sensei_get_sidebar - 10
     */
    do_action( 'sensei_sidebar' );

?>

<?php get_footer(); ?>