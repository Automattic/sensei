<?php
/**
 * The Template for displaying all single lesson meta data.
 *
 * Override this template by copying it to yourtheme/sensei/single-lesson/lesson-meta.php
 *
 * @author 		WooThemes
 * @package 	Sensei/Templates
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $post, $woothemes_sensei, $current_user;

// Complete Lesson Logic
do_action( 'sensei_complete_lesson' );
?>
<section class="lesson-meta" id="lesson_complete"><?php do_action( 'sensei_single_lesson_meta' ); ?></section>

<?php do_action( 'sensei_lesson_meta_extra', $post->ID ); ?>