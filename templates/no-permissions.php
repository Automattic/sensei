<?php
/**
 * The Template for displaying all access restriction error messages.
 *
 * Override this template by copying it to yourtheme/sensei/no-permissions.php
 *
 * @author 		Automattic
 * @package 	Sensei
 * @category    Templates
 * @version     1.9.0
 */
?>

<?php  get_sensei_header();  ?>

<?php
/**
 * This action fires inside the no-permissions.php file. It
 * is place above before all the content.
 *
 * @since 1.9.0
 * @param $post_id
 */
do_action('sensei_no_permissions_before_content', get_the_ID() );
?>

<article <?php post_class( 'no-permission' ) ?> >

    <header>

        <h1><?php the_no_permissions_title(); ?></h1>

    </header>

    <?php
    /**
     * This action fires inside the no-permissions.php file. It
     * is place just before the content.
     *
     * @since 1.9.0
     * @param $post_id
     */
    do_action('sensei_no_permissions_inside_before_content', get_the_ID() );
    ?>

    <section class="entry fix">

        <div class="sensei-message alert">

            <?php the_no_permissions_message( get_the_ID() ); ?>

        </div>

        <p class="excerpt">

            <?php sensei_the_excerpt( get_the_ID() ); ?>

        </p>

    </section>

    <?php
    /**
     * This action fires inside the no-permissions.php file. It
     * is place just after the content.
     *
     * @since 1.9.0
     * @param $post_id
     */
    do_action('sensei_no_permissions_inside_after_content', get_the_ID() );
    ?>

</article><!-- .no-permissions -->

<?php
/**
 * This action fires inside the no-permissions.php file. It
 * is placed outside after the content.
 *
 * @since 1.9.0
 * @param $post_id
 */
do_action('sensei_no_permissions_after_content', get_the_ID() );
?>

<?php get_sensei_footer(); ?>