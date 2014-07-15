<?php
/**
 * Created by PhpStorm.
 * User: lukas
 * Date: 14.07.14
 * Time: 15:46
 */

namespace PressBooks\Lists;


class ListNodeShow {

    /**
     * Complete Strings
     */

    static function getListString($node){
        return(static::getNumber($node)." - ".static::getCaption($node));
    }

    static function getRevString($node){

        return(static::getAcronym($node).": ".static::getNumber($node));
    }

    static function getCaptionPrefix($node){
        $node = static::getTheArray($node);
        if($node["type"] == "img" || $node["type"] == "table"){
            return(static::getAcronym($node)." ".static::getNumber($node).": ");
        }else{
            return(static::getNumber($node)." - ");
        }
    }

    /**
     * Formated Attributes
     */

    static function getCaption($node){
        $node = static::getTheArray($node);
        $caption = apply_filters( 'the_content', $node["caption"] );
        return(strip_tags($caption));
    }
    static function getNumber($node){
        $node = static::getTheArray($node);
        return implode(".", $node["numberArray"]);
    }

    static function getAcronym($node){
        $node = static::getTheArray($node);
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

    /**
     * Private Functions
     */

    static private function getTheArray($node){
        if(is_a($node, "\PressBooks\Lists\ListNode")){
            return($node->getNodeAsArray());
        }
        return($node);
    }

} 