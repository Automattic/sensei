<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 *
 * Renders the [sensei_teachers] shortcode. Will show a list of teachers
 * with links to their archives page.
 *
 * This class is loaded int WP by the shortcode loader class.
 *
 * For the teacher include and excludes you can specify user-names or ids
 *
 * @class Sensei_Shortcode_Teachers
 *
 * @package Content
 * @subpackage Shortcode
 * @author Automattic
 *
 * @since 1.9.0
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

        $include = isset( $attributes['include'] ) ? explode( ',', $attributes['include'] ) : '';
        $exclude = isset( $attributes['exclude'] ) ? explode( ',', $attributes['exclude'] ) : '';

        // convert teacher usernames given to the id
        $this->include = $this->convert_usernames_to_ids( $include );
        $this->exclude = $this->convert_usernames_to_ids( $exclude );

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
                $all_users = $this->users_sort( $all_users );

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

            $user_display_name = $this->get_user_public_name( $user );

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

    /**
     * Convert mixed array of user id and user names to only be an array of user_ids
     *
     * @param array $users
     * @return array $users_ids
     */
    public function convert_usernames_to_ids( $users ){

        // backup
        $users_ids = array();

        if ( is_array($users) ) {

            foreach ($users as $user_id_or_username) {

                if (!is_numeric($user_id_or_username)) {

                    $user_name = $user_id_or_username;
                    $user = get_user_by('login', $user_name);

                    if (is_a($user, 'WP_User')) {
                        $users_ids[] = $user->ID;
                    }

                } else {

                    $user_id = $user_id_or_username;
                    $users_ids[] = $user_id;

                }

            }
        }

        return $users_ids;
    }

    /**
     * Returns the first name and last name or the display name of a user.
     *
     * @since 1.9.0
     *
     * @param $user
     * @return string $user_public_name
     */
    public function get_user_public_name( $user ){

        if (!empty($user->first_name) && !empty($user->last_name)) {

            $user_public_name = $user->first_name . ' ' . $user->last_name;

        }

        else {

            $user_public_name = $user->display_name;

        }

        return $user_public_name;
    }

    /**
     *
     * Sort user objects by user display
     *
     * @since 1.9.0
     *
     * @param $users
     * @return  array $sorted_users
     */
    public function users_sort( $users ){

        $sorted_users = $users;

        uasort( $sorted_users, array( $this, 'custom_user_sort' ) );

        return $sorted_users;
    }

    /**
     * Used in the uasort function to sort users by title
     *
     * @since 1.9.0
     *
     * @param $user_1
     * @param $user_2
     * @return int
     */
    public function custom_user_sort($user_1, $user_2){

        return strcasecmp( $this->get_user_public_name( $user_1 ), $this->get_user_public_name( $user_2 )  );

    }// end custom_user_sort

}// end class