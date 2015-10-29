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
 */
do_action('sensei_no_permissions_before_content');
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
     */
    do_action('sensei_no_permissions_inside_before_content');
    ?>

    <section class="entry fix">

        <div class="sensei-message alert">

            <?php the_no_permissions_message(); ?>

        </div>

        <p class="excerpt">

            <?php sensei_the_excerpt(); ?>

        </p>

    </section>

    <?php
    /**
     * This action fires inside the no-permissions.php file. It
     * is place just after the content.
     *
     * @since 1.9.0
     */
    do_action('sensei_no_permissions_inside_after_content');
    ?>

</article><!-- .no-permissions -->

<?php
/**
 * This action fires inside the no-permissions.php file. It
 * is placed outside after the content.
 *
 * @since 1.9.0
 */
do_action('sensei_no_permissions_after_content');
?>

<?php get_sensei_footer(); ?>