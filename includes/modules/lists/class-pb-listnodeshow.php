<?php
/**
 * Created by PhpStorm.
 * User: lukas
 * Date: 14.07.14
 * Time: 15:46
 */

namespace PressBooks\Lists;


/**
 * Class ListNodeShow
 * Helps formatting list nodes
 * @package PressBooks\Lists
 */
class ListNodeShow {
    /*************************
     * Complete Strings
     *************************/

    /**
     * Returns a string representing the node for a list view
     * @param \PressBooks\Lists\ListNode $node the node
     * @return string
     */
    static function get_list_string($node){
        return(static::get_number($node)." - ".static::get_caption($node));
    }

    /**
     * Returns a string representing the node for a reference view
     * @param \PressBooks\Lists\ListNode $node the node
     * @return string
     */
    static function get_rev_string($node){

        return(static::get_acronym($node).": ".static::get_number($node));
    }

    /**
     * Returns a prefix for captions e.g. "Table 1.1.1: "
     * @param \PressBooks\Lists\ListNode $node the node
     * @return string
     */
    static function get_caption_prefix($node){
        $node = static::get_the_array($node);
        if($node["type"] == "img" || $node["type"] == "table"){
            return(static::get_acronym($node)." ".static::get_number($node).": ");
        }else{
            return(static::get_number($node)." - ");
        }
    }

    /*************************
     * Formated Attributes
     *************************/

    /**
     * Get the caption formatted with the_content and striped from html tags
     * @param \PressBooks\Lists\ListNode $node the node
     * @return string
     */
    static function get_caption($node){
        $node = static::get_the_array($node);
        $caption = apply_filters( 'the_content', $node["caption"] );
        return(strip_tags($caption));
    }

    /**
     * Get the number as string
     * @param \PressBooks\Lists\ListNode $node the node
     * @return string
     */
    static function get_number($node){
        $node = static::get_the_array($node);
        return implode(".", $node["numberArray"]);
    }

    /**
     * Get the acronym for the type of node
     * @param \PressBooks\Lists\ListNode $node the node
     * @return mixed
     */
    static function get_acronym($node){
        $node = static::get_the_array($node);
        $prefix = array();
        $prefix["table"] = "Tab.";
        $prefix["img"] = "Abb.";
        $prefix["h1"] = "Title";
        $prefix["h2"] = "Title";
        $prefix["h3"] = "Title";
        $prefix["h4"] = "Title";
        $prefix["h5"] = "Title";
        $prefix["h6"] = "Title";
        return($prefix[$node["type"]]);
    }

    /*************************
     * Private Functions
     *************************/

    /**
     * Returns the node as array
     * @param \PressBooks\Lists\ListNode $node the node
     * @return array
     */
    static private function get_the_array($node){
        if(is_a($node, "\PressBooks\Lists\ListNode")){
            return($node->getNodeAsArray());
        }
        return($node);
    }

} 