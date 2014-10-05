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
     * @param string $link href for the link
     * @return string
     */
    static function get_list_string($node, $link=false){
        $node = static::get_the_array($node);
        $link = static::get_href($node, $link);
        $num = static::get_number($node);
        if( $num != "" ){
            $output = '<a class="ref-link ref-link-'.$node["type"].'" href="'.$link.'"><span class="ref-link-number">'.$num." - </span>".static::get_caption($node).'</a>';
        }else{
            $output = '<a class="ref-link ref-link-'.$node["type"].'" href="'.$link.'">'.static::get_caption($node).'</a>';
        }


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
     * @param string $settings settings for the output (b: don't show brackets; a: don't show acronym; n: don't show number; C: show caption);
     * @param string|null $caption a caption you want to set
     * @return string
     */
    static function get_ref_string($node, $settings="", $caption = null){
        $node = static::get_the_array($node);
        $num = static::get_number($node);

        $db = true;
        $da = true;
        $dn = ($num != "");
        $dc = !$dn;

        $db = strpos($settings, "b") !== false ? false : $db;
        $da = strpos($settings, "a") !== false ? false : $da;
        $dn = strpos($settings, "n") !== false ? false : $dn;
        $dc = strpos($settings, "C") !== false ? true : $dc;

        if(!is_null( $caption ) && $caption!=""){
            $dc = true;
        }else{
            $caption = static::get_caption($node);
        }

        $output = $da ? '<span class="ref-link-acronym">'.static::get_acronym($node).":</span>" : "";
        $output .= $da && $dn ? " " : "";
        $output = $dn ? '<span class="ref-link-number">'.$output.$num."</span>" : $output;
        $output .= $dc && $output != "" ? " " : "";
        $output .= $dc && $dn ? "- " : "";
        $output .= $dc ? $caption : "";
        $output = '<a class="ref-link ref-link-'.$node["type"].'" href="'.static::get_href($node).'">'.$output.'</a>';
        $output = $db ? '( '.$output.' )' : $output;
        /**
         * Filter the default lists reference string output.
         *
         * @param string $output  The ref string output.
         * @param array  $node    The node
         */
        $output = apply_filters( 'pb_lists_show_ref_string', $output, $node );

        return($output);
    }

    /**
     * Returns a prefix for captions e.g. "Table 1.1.1: "
     * @param \PressBooks\Lists\ListNode $node the node
     * @param boolean $pure With out html tags
     * @return string
     */
    static function get_caption_prefix($node, $pure = false){
        $node = static::get_the_array($node);
        $num = static::get_number($node);

        if( $num != ""){
            if(static::real_element($node)){
                $output = "";
                $output .= !$pure ? '<span class="caption-number caption-number-'.$node["type"].'"><span class="caption-acronym">' : '';
                $output .= static::get_acronym($node).' ';
                $output .= !$pure ? '</span>' : '';
                $output .= $num.": ";
                $output .= !$pure ? "</span>" : "";
            }else{
                $output = "";
                $output .= !$pure ? '<span class="caption-number caption-number-'.$node["type"].'">' : '';
                $output .= $num." - ";
                $output .= !$pure ? "</span>" : '';
            }
        }else{
            $output = "";
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
        $output = $node["caption"];
        $output = strip_shortcodes($output);
        //$output = apply_filters( 'the_content', $node["caption"] ); //Because it replaces already running the_content filters
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
        $output = "";
        if($node["active"]){
            $options = get_option( 'pressbooks_theme_options_global' );
            if (@$options['chapter_numbers'] ){
                //If chapters have numbers
                if(static::element($node)){
                    //Numbers for everything except parts, chapters, front and back matters
                    $p = get_post( $node['pid'] );
                    $type = pb_get_section_type( $p );
                    if(Lists::add_numbers_to_list_elements() && $type !== 'numberless' && get_post_meta( $node['pid'], 'invisible-in-toc', true ) !== 'on'){
                        //If the chapter is in the TOC
                        $hlevel = Lists::add_numbers_to_heading_levels();
                        if(($node["type"] != "h1" && $node["type"] != "h2" && $node["type"] != "h3" && $node["type"] != "h4" && $node["type"] != "h5" && $node["type"] != "h6")
                        || ($node["type"] == "h1" && $hlevel >= 1) || ($node["type"] == "h2" && $hlevel >= 2) || ($node["type"] == "h3" && $hlevel >= 3) || ($node["type"] == "h4" && $hlevel >= 4) || ($node["type"] == "h5" && $hlevel >= 5) || ($node["type"] == "h6" && $hlevel >= 6)){
                            //If it is not a heading or the heading should get a number a it
                            $post_name = pb_get_post_name($node["pid"]);
                            $node["numberArray"][0] = pb_get_chapter_number($post_name);
                            $output = implode(".", $node["numberArray"]);
                        }
                    }
                }else{
                    //Parts, chapters, front and back matters
                    if($node["type"] != "part"){
                        $post_name = pb_get_post_name($node["pid"]);
                        $output = pb_get_chapter_number($post_name);
                    }else{
                        $output = pb_get_part_number($node["pid"]);
                    }
                }
            }else{
                //If chapters have no number, output ongoing number for none headings
                if(Lists::add_numbers_to_list_elements()){
                    if(static::real_element($node)){
                        $output = $node["onGoingNumber"];
                    }
                }
            }
        }
        $output = $output !== 0 ? $output : "";
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
        $prefix["table"] = __( 'Tab.', 'pressbooks' );
        $prefix["img"] = __( 'Fig.', 'pressbooks' );
        $prefix["h1"] = __( 'Chapter', 'pressbooks' );
        $prefix["h2"] = __( 'Chapter', 'pressbooks' );
        $prefix["h3"] = __( 'Chapter', 'pressbooks' );
        $prefix["h4"] = __( 'Chapter', 'pressbooks' );
        $prefix["h5"] = __( 'Chapter', 'pressbooks' );
        $prefix["h6"] = __( 'Chapter', 'pressbooks' );
        $prefix["chapter"] = __( 'Chapter', 'pressbooks' );
        $prefix["front-matter"] = __( 'Chapter', 'pressbooks' );
        $prefix["back-matter"] = __( 'Chapter', 'pressbooks' );
        $prefix["part"] = __( 'Part', 'pressbooks' );
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

    /**
     * Get the link of the node
     * @param \PressBooks\Lists\ListNode $node the node
     * @param string $link href for the link
     * @return mixed
     */
    static function get_href($node, $link=false){
        $node = static::get_the_array($node);
        if($link === false){
            if($node["type"] != "part"){
                $link = get_permalink($node["pid"]);
            }else{
                if(get_post_meta( $node['pid'], 'pb_part_content', true )){
                    $link = get_permalink($node["pid"]);
                }else{
                    $lookup = \PressBooks\Book::getBookStructure();
                    foreach ( $lookup["part"] as $key => $val ) {
                        if($val["ID"] == $node['pid']){
                            if(count($val["chapters"])){
                                $link = get_permalink($val["chapters"][0]["ID"]);
                            }else{
                                $link = get_permalink($node["pid"]);
                            }
                        }
                    }
                }
            }
        }

        if(!static::element($node)){
            return($link);
        }
        return($link.'#'.$node["id"]);
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
        if(is_a($node, "\PressBooks\Lists\ListNode") || is_a($node, "\PressBooks\Lists\ListChapter")){
            return($node->getNodeAsArray());
        }
        return($node);
    }

    /**
     * If it is a list element
     * @param \PressBooks\Lists\ListNode $node the node
     * @return array
     */
    static private function element($node){
        $node = static::get_the_array($node);
        if($node["type"] != "chapter" && $node["type"] != "front-matter" && $node["type"] != "back-matter" && $node["type"] != "part"){
            return true;
        }
        return false;
    }

    /**
     * If it is a real list element
     * Not a chapter, part or heading
     * @param \PressBooks\Lists\ListNode $node the node
     * @return array
     */
    static private function real_element($node){
        $node = static::get_the_array($node);
        if($node["type"] != "h1" && $node["type"] != "h2" && $node["type"] != "h3" && $node["type"] != "h4" && $node["type"] != "h5" && $node["type"] != "h6" && $node["type"] != "chapter" && $node["type"] != "front-matter" && $node["type"] != "back-matter" && $node["type"] != "part"){
            return true;
        }
        return false;
    }

} 