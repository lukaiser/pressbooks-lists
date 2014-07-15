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
    static function display_hierarchical_list($list){
        if(is_a($list, "\PressBooks\Lists\iList")){
            $list = $list->getHierarchicalArray();
        }

        $content = "<ul>";
        foreach($list as $chapter){
            $content .= static::output_node($chapter);
        }
        $content .="</ul>";
        return $content;
    }

    /**
     * Handles a node an its sub nodes
     * @param array $node the node
     * @return string
     */
    private static function output_node($node){
        $content = "";
        if(array_key_exists("caption",$node) && $node["active"]){
            $content = "<li>";
            $content .= ListNodeShow::get_list_string($node);

            if(count($node["childNodes"])>0){
                $content .= "<ul>";
                foreach($node["childNodes"] as $e2){
                    $content .= static::output_node($e2);
                }
                $content .= "</ul>";
            }
            $content .= "</li>";
        }else{
            if(count($node["childNodes"])>0){
                foreach($node["childNodes"] as $e2){
                    $content .= static::output_node($e2);
                }
            }
        }
        return $content;
    }
} 