<?php
/**
 * Created by PhpStorm.
 * User: lukas
 * Date: 14.07.14
 * Time: 16:17
 */

namespace PressBooks\Lists;


/**
 * Class ListShow
 * Helps displaying lists as lists
 * @package PressBooks\Lists
 */
class ListShow {
    /**
     * Returns a hierarchical HTML UL list
     * @param \PressBooks\Lists\iList $list the list
     * @return string
     */
    static function hierarchical_list($list, $listtype = "ul"){
        if(is_a($list, "\PressBooks\Lists\iList")){
            $list = $list->getHierarchicalArray();
        }

        $content = "<".$listtype.">";
        foreach($list as $chapter){
            $p = get_post( $chapter['pid'] );
            $type = pb_get_section_type( $p );
            if( $type !== 'numberless' && get_post_meta( $chapter['pid'], 'invisible-in-toc', true ) !== 'on'){
                $content .= static::hierarchical_list_node($chapter, $depth = -1, $link=false, $listtype);
            }
        }
        $content .="</".$listtype.">";
        /**
         * Filter the default lists hierarchical list output.
         *
         * @param string $content  The hierarchical list string output.
         * @param array  $list    The node
         */
        $content = apply_filters( 'pb_lists_show_hierarchical_list', $content, $list );
        return $content;
    }
    /**
     * Returns a hierarchical HTML UL list of a Chapter
     * @param \PressBooks\Lists\ListChapter $list the chapter
     * @param int $depth How many levels should be displayed?
     * @param string $link href for the link
     * @return string
     */
    static function hierarchical_chapter($chapter, $depth = 3, $link=false, $listtype = "ul"){
        if(is_a($chapter, "\PressBooks\Lists\ListChapter")){
            $chapter = $chapter->getHierarchicalArray();
        }
        $content = "";
        if(count($chapter["childNodes"])>0 && $depth > 1){
            $content .= '<'.$listtype.' class="sections ref-list-'.$chapter["childNodes"][0]["type"].'">';
            $content .= static::hierarchical_list_node($chapter, $depth, $link, $listtype);
            $content .="</".$listtype.">";
        }
        /**
         * Filter the default lists hierarchical list output.
         *
         * @param string $content  The hierarchical list string output.
         * @param array  $list    The node
         */
        $content = apply_filters( 'pb_lists_show_hierarchical_chapter', $content, $chapter );
        return $content;
    }


    /**
     * Handles a node an its sub nodes
     * @param array $node the node
     * @param int $depth How many levels should be displayed?
     * @param string $link href for the link
     * @return string
     */
    private static function hierarchical_list_node($node, $depth = -1, $link=false, $listtype = "ul"){
        if($depth != -1){
            $depth --;
        }
        $content = "";
        if($node["type"] != "chapter" && $node["type"] != "front-matter" && $node["type"] != "back-matter" && $node["type"] != "part" && $node["active"]){
            $content = '<li class="section">';
            $content .= ListNodeShow::get_list_string($node, $link);

            if(count($node["childNodes"])>0 && ($depth == -1 || $depth > 0)){
                $content .= '<'.$listtype.' class="sections ref-list-'.$node["childNodes"][0]["type"].'">';
                foreach($node["childNodes"] as $e2){
                    $content .= static::hierarchical_list_node($e2, $depth, $link, $listtype);
                }
                $content .= "</".$listtype.">";
            }
            $content .= "</li>";
        }else{
            if(count($node["childNodes"])>0 && ($depth == -1 || $depth > 0)){
                foreach($node["childNodes"] as $e2){
                    $content .= static::hierarchical_list_node($e2, $depth, $link, $listtype);
                }
            }
        }
        /**
         * Filter the default lists hierarchical list node output.
         *
         * @param string $content  The hierarchical node string output.
         * @param array  $node    The node
         */
        $content = apply_filters( 'pb_lists_show_hierarchical_list_node', $content, $node );
        return $content;
    }
} 