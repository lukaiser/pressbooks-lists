<?php
/**
 * Contains PressBooks-specific additions to TinyMCE, specifically custom CSS classes.
 *
 * @author  PressBooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks\Lists;


/**
 * Class Lists
 * Main List Class
 * Handles all the hooks and provides functions for external parts of PressBooks
 * @package PressBooks\Lists
 */
class Lists {

	/**********************
	 * Init Hooks
     **********************/

    /**
     * Init Admin Hooks
     */
    static function init_admin_hooks( ) {
        add_filter("mce_external_plugins", '\PressBooks\Lists\Lists::register_tinymce_plugin');
        add_editor_style( PB_PLUGIN_URL.'assets/css/pbmanagelists.css' );
        add_action('wp_ajax__ajax_fetch_lists_list', '\PressBooks\Lists\Lists::_ajax_fetch_lists_list_callback');
        add_filter( 'wp_insert_post_data', '\PressBooks\Lists\Lists::wp_insert_post_data_handler', '10', 2 );
    }

    /**
     * Init Site Hooks
     */
    static function init_hooks( ){
        add_filter( 'the_content', '\PressBooks\Lists\Lists::handle_content', 20 );
        add_shortcode( 'rev', '\PressBooks\Lists\Lists::handle_rev_shortcode' );
        add_shortcode( 'LOT', '\PressBooks\Lists\Lists::handle_LOT_shortcode' );
        add_shortcode( 'LOI', '\PressBooks\Lists\Lists::handle_LOI_shortcode' );
    }

    /**********************
     * Hooks
     **********************/

    /**
     * Adds the tinymce plugin to the editor
     * @param $plugin_array
     * @return array
     */
    static function register_tinymce_plugin($plugin_array){
        $plugin_array['pbmanagelists'] = PB_PLUGIN_URL.'assets/js/pbmanagelists.js';
        return $plugin_array;
    }

    /**
     * Adds prefix to captions
     * @param $content
     * @return string
     */
    static function handle_content($content){

        $bl = \PressBooks\Lists\Lists::get_book_lists();
        foreach($bl as $l){
            $content = $l->addCaptionPrefix($content);
        }
        return $content;
    }

    /**
     * Handles ajax requests by Lists_List_Table
     */
    static function _ajax_fetch_lists_list_callback(){
        error_reporting(E_ERROR | E_PARSE);
        $GLOBALS["hook_suffix"] = $_REQUEST["hook_suffix"];
        if(! empty( $_REQUEST['list_type'] ) && array_key_exists($_REQUEST['list_type'], Lists::get_book_lists())){
            $chapters = $_REQUEST['list_type'] == "h";
            $list_table = new \PressBooks\Lists\Lists_List_Table($_REQUEST['list_type'], $chapters);
            $list_table->ajax_response();
        }
    }

    /**
     * Adds ids and in-list class to DOMElements that should have one before saving a post
     * @param $data
     * @param $postarr
     * @return mixed
     */
    static function wp_insert_post_data_handler($data , $postarr){

        $lists = static::get_initial_lists();

        $content = $data["post_content"];
        $content = wp_unslash($content);
        foreach($lists as $list){
            $ncontent = $list->contentAddMissingIdAndClasses($content);
            if($ncontent){
                $content = $ncontent;
            }
        }
        $content = wp_slash($content);
        $data["post_content"] = $content;
        return($data);
    }

    /**
     * Handles the reverence (rev) shortcode
     * @param $atts
     * @return string
     */
    static function handle_rev_shortcode($atts){
        extract( shortcode_atts(
                array(
                    'id' => false,
                ), $atts )
        );
        if(!$id){
            return "";
        }
        $node = static::get_list_node_by_id($id);
        if($node){
            return ListNodeShow::get_rev_string($node);
        }else{
            return "";
        }
    }

    /**
     * Handles the list of table (LOT) shortcode
     * @param $atts
     * @return string
     */
    static function handle_LOT_shortcode($atts){
        $bl = \PressBooks\Lists\Lists::get_book_lists();
        return ListShow::hierarchical_list($bl["table"]);
    }

    /**
     * Handles the list of figure (LOF) shortcode
     * @param $atts
     * @return string
     */
    static function handle_LOI_shortcode($atts){
        $bl = \PressBooks\Lists\Lists::get_book_lists();
        return ListShow::hierarchical_list($bl["img"]);
    }

    /**********************
     * Functions
     **********************/

    /**
     * Returns a array of all the Lists
     * @param bool $idsAndClasses If Ids and in-list class should be added to DOMElements not having them
     * @return array
     */
    static function get_book_lists($idsAndClasses = false){
        // -----------------------------------------------------------------------------
        // Is cached?
        // -----------------------------------------------------------------------------

        global $blog_id;
        $cache_id = "book-lists-$blog_id";
        $book_lists = wp_cache_get( $cache_id, 'pb' );
        if ( $book_lists && !$idsAndClasses ) {
            return $book_lists;
        }

        // -----------------------------------------------------------------------------
        // Initiate Lists
        // -----------------------------------------------------------------------------

        $lists = static::get_initial_lists();

        // -----------------------------------------------------------------------------
        // Get Content
        // -----------------------------------------------------------------------------

        $book_contents = \PressBooks\Book::getBookContents();

        // Do root level structures first.
        foreach ( $book_contents as $type => $struct ) {

            if ( preg_match( '/^__/', $type ) )
                continue; // Skip __magic keys

            foreach ( $struct as $i => $val ) {

                if ( isset( $val['post_content'] ) && $val["post_status"] == 'publish' ) {
                    static::get_book_lists__handle_chapter($lists, $val['post_content'], $val['ID'], $val['post_type'], $idsAndClasses);
                }

                if ( 'part' == $type ) {

                    // Do chapters, which are embedded in part structure
                    foreach ( $book_contents[$type][$i]['chapters'] as $j => $val2 ) {

                        if ( isset( $val2['post_content'] ) && $val2["post_status"] == 'publish' ) {
                            static::get_book_lists__handle_chapter($lists, $val2['post_content'], $val2['ID'], $val2['post_type'], $idsAndClasses);
                        }

                    }
                }
            }
        }

        $book_lists = $lists;

        // -----------------------------------------------------------------------------
        // Cache & Return
        // -----------------------------------------------------------------------------

        wp_cache_set( $cache_id, $book_lists, 'pb', 86400 );

        return $book_lists;
    }

    /**
     * Get a ListNode by its Id
     * @param string $id the node id
     * @return ListNode|false
     */
    static function get_list_node_by_id($id){
        $bl = static::get_book_lists();
        foreach($bl as $l){
            if($node = $l->getNodeById($id)){
                return($node);
            }
        }
        return(false);
    }

    /**
     * Get a chapter of a List
     * @param string $list list type
     * @param string $id PID of chapter
     * @return ListChapter|false
     */
    static function get_chapter_list_by_pid($list, $id){
        $bl = static::get_book_lists();
        if(array_key_exists($list, $bl)){
            return($bl[$list]->getChapterByPid($id));
        }
        return(false);
    }

    /**********************
     * Private Functions
     **********************/

    /**
     * Handles a chapter while creating the lists
     * @param array $lists the lists
     * @param string $content the content of the chapter
     * @param int $pid the id of the post
     * @param string $type the type of the content
     * @param bool $idsAndClasses If Ids and in-list class should be added to DOMElements not having them
     */
    private static function get_book_lists__handle_chapter($lists, $content, $pid, $type, $idsAndClasses = false){
        if($idsAndClasses){
            $changed = false;
            foreach($lists as $list){
                $ncontent = $list->contentAddMissingIdAndClasses($content);
                if($ncontent){
                    $changed = true;
                    $content = $ncontent;
                }
            }
            if($changed){
                $new_post = array(
                    'ID'           => $pid,
                    'post_content' => $content
                );
                wp_update_post ($new_post);
            }
        }
        foreach($lists as $list){
            $list->addContentToList($content, $pid, $type);
        }
    }

    /**
     * Get all existing lists (empty)
     * @return array
     */
    private static function get_initial_lists(){
        $lists = array();

        $lists["h"] = new \PressBooks\Lists\XpathList(array("h1", "h2", "h3", "h4", "h5", "h6"), ".");
        $lists["img"] = new \PressBooks\Lists\ImgList("img", "@alt");
        $lists["table"] = new \PressBooks\Lists\XpathList("table", "caption");

        return $lists;
    }

}
