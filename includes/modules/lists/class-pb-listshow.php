<?php
/**
 * Created by PhpStorm.
 * User: lukas
 * Date: 14.07.14
 * Time: 16:17
 */

namespace PressBooks\Lists;


class ListShow {
    static function displayHierarchicalList($list){
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

    private static function output_node($e){
        $content = "";
        if(array_key_exists("caption",$e) && $e["active"]){
            $content = "<li>";
            $content .= ListNodeShow::getListString($e);

            if(count($e["childNodes"])>0){
                $content .= "<ul>";
                foreach($e["childNodes"] as $e2){
                    $content .= static::output_node($e2);
                }
                $content .= "</ul>";
            }
            $content .= "</li>";
        }else{
            if(count($e["childNodes"])>0){
                foreach($e["childNodes"] as $e2){
                    $content .= static::output_node($e2);
                }
            }
        }
        return $content;
    }
} 