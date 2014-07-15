<?php
/**
 * Created by PhpStorm.
 * User: lukas
 * Date: 08.07.14
 * Time: 09:55
 */

namespace PressBooks\Lists;


/**
 * Class ListChapter
 * Represents all the list items of a Chapter of the book
 * @package PressBooks\Lists
 */
class ListChapter {

    /**
     * @var \PressBooks\Lists\iList the list the chapter is in
     */
    public $list;

    /**
     * @var int the id of the post
     */
    public $number;
    /**
     * @var array all the nodes of the chapter
     */
    public $child;

    /**
     * @param \PressBooks\Lists\iList $list the list the chapter is in
     * @param int $number the id of the post
     */
    function __construct($list = null, $number){
        $this->list = $list;
        $this->number = $number;
        $this->child = array();
    }

    /**
     * Adds a child to the chapter
     * @param \PressBooks\Lists\ListNode $child the child
     */
    function addChild($child){
        $child->chapter = $this;
        $this->child[] = $child;
    }

    /**
     * Returns a array of the numbers representing the position of the child
     * @param \PressBooks\Lists\ListNode $child
     * @return array
     */
    function getNumberingOfChild($child){
        $cna = array();
        $cna[] = $this->number;
        if(is_array($this->list->getTypes())){
            foreach($this->list->getTypes() as $type){
                $cna[] = 0;
            }
        }else{
            $cna[] = 0;
        }
        foreach($this->child as $c){
            if($c->active){
                $nn = $this->list->getDepthOfTagname($c->type)+1;
                $cna[$nn] ++;
                for($i = $nn+1; $i <7; $i++){
                    $cna[$i] = 0;
                }
                if($child == $c){
                    return(array_slice($cna, 0, $nn+1));
                }
            }
        }
    }

    /**
     * Returns an array of nodes.
     * All nodes are childs of the array
     * Active and Inactive ones
     * @return array
     */
    function getFlatArray(){
        $out = array();
        foreach($this->child as $child){
            $out[$child->id] = $child->getNodeAsArray();
        }
        return($out);
    }

    /**
     * Returns an array representing the hierarchy of the nodes
     * Only active nodes
     * @return array
     */
    function getHierarchicalArray(){
        $out = array();
        $out["childNodes"] = array();
        foreach($this->child as $child){
            if($child->active){
                $a = $child->getNodeAsArray();
                $a["childNodes"] = array();
                $down = $this->list->getDepthOfTagname($child->type);
                $in = &$out["childNodes"];
                for($i = 0; $i < $down; $i++){
                    if(count($in) == 0){
                        $in[] = array("childNodes" => array());
                    }
                    $in = &$in[count($in)-1]["childNodes"];
                }
                $in[] = $a;
            }
        }
        return $out;
    }

    /**
     * Returns a node by a id
     * @param string $id id of the node
     * @return \PressBooks\Lists\ListNode|false
     */
    function getNodeById($id){
        foreach($this->child as $child){
            if($n = $child->getNodeById($id)){
                return $n;
            }
        }
        return false;
    }

} 