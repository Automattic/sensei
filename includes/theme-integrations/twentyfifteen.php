<?php
/**
 * Class Sensei_Twentyfifteen
 *
 * Responsible for wrapping twenty fifteen theme Sensei content
 * with the correct markup
 *
 * @package Views
 * @subpackage Theme-Integration
 * @author Automattic
 *
 * @since 1.9.0
*/
Class Sensei_Twentyfifteen extends Sensei__S {

    /**
     * Output opening wrappers
     * @since 1.9.0
     */
    public function wrapper_start(){

        // output inline styles
        $this->print_styles();

        // call the parent starting wrappers
        parent::wrapper_start();

    }


    /**
     * Output the style for the
     * twenty fifteen theme integration.
     *
     * @since 1.9.0
     */
    private function print_styles(){?>

        <style>
            @media screen and (min-width: 59.6875em){
                #main article.lesson,
                #main article.course,
                #main #post-entries,
                .sensei-breadcrumb {
                    padding-top: 8.3333%;
                    margin: 0 8.3333%;
                    box-shadow: 0 0 1px rgba(0, 0, 0, 0.15);
                    background-color: #fff;
                    padding: 1em 2em 2em;
                }

                #main .course-lessons .lesson {
                    margin: 0;
                }

                #main #post-entries {
                    padding: 1em 2em;
                    overflow: hidden;
                }

                #main article.lesson ol {
                    list-style-position: inside;
                }

                .sensei-course-filters {
                    margin: 0 8.3333%;
                    padding: 0;
                    box-shadow: 0 0 1px rgba(0, 0, 0, 0.15);
                    background: white;
                    padding: 2%;
                }

                .sensei-ordering {
                    text-align: right;
                    float: right;
                    margin: 0 8.3333%;
                    padding: 2%;
                }
                .archive-header h1{
                    padding: 2%;
                    background: white;
                    margin: 2% 8.3333%;
                }

                nav.sensei-pagination, .post-type-archive .course-container li{
                    padding: 2% !important;
                    background: white !important;
                    margin: 2% 8.3333% !important;
                    width: 83.333% !important
                }

                nav.sensei-pagination{
                    text-align: center;
                }

                nav.sensei-pagination .page-numbers{
                    margin-bottom: 0;
                }
                nav.sensei-pagination li a,
                nav.sensei-pagination li span.current {
                    display: block;
                    border: 2px solid #ddd;
                    margin-right: 2px;
                    padding: 0.2em 0.5em;
                    background: #eee;
                }

                nav.sensei-pagination li span.current{
                    background: white;
                }
            }
        </style>

    <?php }

} // end class
