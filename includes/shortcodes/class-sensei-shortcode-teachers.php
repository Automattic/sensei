<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 *
 * Renders the [sensei_teachers] shortcode. Will show a list of teachers
 * with links to their archives page.
 *
 * This class is loaded int WP by the shortcode loader class.
 *
 * @class Sensei_Shortcode_Teachers
 * @since 1.9.0
 * @package Sensei
 * @category Shortcodes
 * @author 	WooThemes
 */
class Sensei_Shortcode_Teachers implements Sensei_Shortcode_Interface {

    /**
     * @var WP_User_Query keeps a reference to the user query created
     */
    protected $user_query;

    /**
     * @var which user id's to include
     */
    protected $include;

    /**
     * @var which user id's to exclude
     */
    protected $exclude;

    /**
     * Setup the shortcode object
     *
     * @since 1.9.0
     * @param array $attributes
     * @param string $content
     * @param string $shortcode the shortcode that was called for this instance
     */
    public function __construct( $attributes, $content, $shortcode ){

        $this->include = isset( $attributes['include'] ) ? explode( ',', $attributes['include'] ) : '';
        $this->exclude = isset( $attributes['exclude'] ) ? explode( ',', $attributes['exclude'] ) : '';

        $this->setup_teacher_query();

    }

    /**
     *
     * Setup the user query that will be used in the render method
     *
     * @since 1.9.0
     */
    protected function setup_teacher_query(){

        $user_query_args = array(
            'role' => 'teacher',
        );

        $this->user_query = new WP_User_Query( $user_query_args );

    }// end setup _course_query

    /**
     * Rendering the shortcode this class is responsible for.
     *
     * @return string $content
     */
    public function render(){

        $all_users = $this->user_query->get_results();
        // if the user has specified more users add them as well.
        if( ! empty( $this->include ) ){

            $included_users_query = new WP_User_Query( array( 'include' => $this->include ) );
            $included_users = $included_users_query->get_results();
            if( ! empty( $included_users ) ){

                $merged_users = array_merge( $all_users, $included_users );
                $all_users = $this->users_unique( $merged_users );

            }

        }

        // exclude the users not wanted
        if( ! empty( $this->exclude ) ){

            $all_users = $this->exclude_users( $all_users, $this->exclude );

        }

        if( ! count( $all_users )> 0  ){
            return '';
        }


        $users_output = '';

        foreach ( $all_users as $user ) {

            $user_display_name = $user->first_name . ' ' . $user->last_name;
            if( empty( $user_display_name ) ){
                $user_display_name = $user->display_name;
            }


            /**
             * Sensei teachers shortcode list item filter
             *
             * @since 1.9.0
             *
             * @param string $teacher_li the html for the teacher li
             * @param WP_User $user
             */
            $users_output .= apply_filters( 'sensei_teachers_shortcode_list_item', '<li class="teacher"><a href="'. get_author_posts_url( $user->ID ) . '">'. $user_display_name .  '<a/></li>', $user );

        }

        return '<ul class="sensei-teachers">' . $users_output . '</ul>';

    }// end render

    /**
     * remove duplicate user objects from and array of users
     *
     * @since 1.9.0
     *
     * @param array $users{
     *   @type WP_User
     * }
     *
     * @return array $unique_users {
     *   @type WP_User
     * }
     */
    public  function users_unique( $users ){

        $array_unique_users_ids = array();
        foreach( $users as $index => $user ){

            if(  in_array( $user->ID,  $array_unique_users_ids)  ){

                // exclude this user as it is already in the list
                unset( $users[ $index ] );

            }else{

                // add teh user to the list of users
                $array_unique_users_ids[] = $user->ID;

            }

        }

        return $users;

    }// end users_unique

    /**
     * Exclude users based ont he ids given.
     *
     * @since 1.9.0
     *
     * @param array $users
     * @param array $exclude_ids
     * @return array
     */
    public function exclude_users( $users, $exclude_ids ){

        foreach( $users as $index => $user ){

            if( in_array( $user->ID, $exclude_ids )  ){

                // remove the user from the list
                unset( $users[ $index ] );

            }

        }

        return $users;

    }// end exclude_users

}// end class