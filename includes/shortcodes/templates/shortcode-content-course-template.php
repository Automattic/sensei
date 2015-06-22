<?php
/**
 * Created by PhpStorm.
 * User: dwain
 * Date: 6/22/15
 * Time: 3:25 PM
 */
?>

<article class="<?php echo esc_attr( join( ' ', get_post_class( array( 'course', 'post' ), get_the_ID() ) ) ); ?>">

        <?php do_action( 'sensei_course_image', get_the_ID() ); ?>
        <?php do_action( 'sensei_course_archive_course_title', get_post() ); ?>


    <section class="entry">
        <?php
        do_action( 'sensei_course_meta', get_post() );

        if( has_excerpt() ){
        ?>
            <p class="course-excerpt">
                <?php
                    the_excerpt();
                ?>
            </p>
        <?php } ?>

        <?php
        // Meta data
        $preview_lesson_count = intval( Sensei()->course->course_lesson_preview_count( get_the_ID() ) );
        $is_user_taking_course = WooThemes_Sensei_Utils::user_started_course( get_the_ID(), get_current_user_id() );

        if ( 0 < $preview_lesson_count && !$is_user_taking_course ) {
            ?>
            <p class="sensei-free-lessons">
                <a href="<?php echo get_permalink(); ?>">
                    <?php _e( 'Preview this course', 'woothemes-sensei' ) ?>
                </a>
                - <?php echo sprintf( __( '(%d preview lessons)', 'woothemes-sensei' ), $preview_lesson_count ) ; ?>
            </p>
        <?php } ?>

    </section>

</article>