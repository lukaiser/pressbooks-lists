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
     * @var array all the nodes of the chapter
     */
    public $child;
    /**
     * @var int the id of the post
     */
    public $pid;
    /**
     * @var string the type of the post
     */
    public $type;

    /**
     * @param \PressBooks\Lists\iList $list the list the chapter is in
     * @param int $pid the pid
     * @param string $type the type of the content
     */
    function __construct($list = null, $pid, $type){
        $this->list = $list;
        $this->pid = $pid;
        $this->type = $type;
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
        $cna[] = "";
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
     * Returns a ongoing numbers representing the position of the child
     * @param \PressBooks\Lists\ListNode $child
     * @return int
     */
    function getOnGoingNumberOfChild($child){
        $i = 0;
        $p = get_post( $this->pid );
        $type = pb_get_section_type( $p );
        if( $type == 'numberless' || get_post_meta( $this->pid, 'invisible-in-toc', true ) == 'on'){
            return(0);
        }
        foreach($this->child as $node){
            if($node->active){
                $i++;
                if($node === $child){
                    return($i);
                }
            }

        }
        return(-$i);
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
     * Returns an array of nodes and Chapters.
     * All nodes are children of the array
     * Active and Inactive ones
     * @return array
     */
    function getFlatArrayWithChapter(){
        $out = array();
        $out[$this->pid] = array("pid"=>$this->pid, "type"=>$this->type);
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
        $out["pid"] = $this->pid;
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