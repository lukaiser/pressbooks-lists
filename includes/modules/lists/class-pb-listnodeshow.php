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
        $node = static::get_the_array($node);
        $output = '<a class="rev-link" href="'.get_permalink($node["pid"]).'#'.$node["id"].'">'.static::get_number($node)." - ".static::get_caption($node).'</a>';
        /**
         * Filter the default lists list string output.
         *
         * @param string $output  The list string output.
         * @param array  $node    The node
         */
        $output = apply_filters( 'pb_lists_show_list_string', $output, $node );
        return($output);
    }

    /**
     * Returns a string representing the node for a reference view
     * @param \PressBooks\Lists\ListNode $node the node
     * @return string
     */
    static function get_rev_string($node){
        $node = static::get_the_array($node);
        $output = '( '.'<a class="rev-link" href="'.get_permalink($node["pid"]).'#'.$node["id"].'">'.static::get_acronym($node).": ".static::get_number($node).'</a>'.' )';
        /**
         * Filter the default lists reference string output.
         *
         * @param string $output  The rev string output.
         * @param array  $node    The node
         */
        $output = apply_filters( 'pb_lists_show_rev_string', $output, $node );
        return($output);
    }

    /**
     * Returns a prefix for captions e.g. "Table 1.1.1: "
     * @param \PressBooks\Lists\ListNode $node the node
     * @return string
     */
    static function get_caption_prefix($node){
        $node = static::get_the_array($node);
        if($node["type"] == "img" || $node["type"] == "table"){
            $output = static::get_acronym($node)." ".static::get_number($node).": ";
        }else{
            $output = static::get_number($node)." - ";
        }
        /**
         * Filter the default lists caption prefix string output.
         *
         * @param string $output  The caption string output.
         * @param array  $node    The node
         */
        $output = apply_filters( 'pb_lists_show_caption_prefix', $output, $node );
        return($output);
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
        $output = apply_filters( 'the_content', $node["caption"] );
        $output = strip_tags($output);
        /**
         * Filter the default lists caption string output.
         *
         * @param string $output  The caption string output.
         * @param array  $node    The node
         */
        $output = apply_filters( 'pb_lists_show_caption', $output, $node );
        return($output);
    }

    /**
     * Get the number as string
     * @param \PressBooks\Lists\ListNode $node the node
     * @return string
     */
    static function get_number($node){
        $node = static::get_the_array($node);
        $output = implode(".", $node["numberArray"]);
        /**
         * Filter the default lists number string output.
         *
         * @param string $output  The number string output.
         * @param array  $node    The node
         */
        $output = apply_filters( 'pb_lists_show_number', $output, $node );
        return($output);
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
        $output = $prefix[$node["type"]];
        /**
         * Filter the default lists acronym string output.
         *
         * @param string $output  The acronym string output.
         * @param array  $node    The node
         */
        $output = apply_filters( 'pb_lists_show_acronym', $output, $node );
        return($output);
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