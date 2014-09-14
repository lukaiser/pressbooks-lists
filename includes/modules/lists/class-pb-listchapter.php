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
     * @var bool if the node is active and in the list or not
     */
    public $active;
    /**
     * @var string the caption of the node
     */
    public $caption;
    /**
     * @var string the post name
     */
    public $post_name;

    /**
     * @param \PressBooks\Lists\iList $list the list the chapter is in
     * @param int $pid the pid
     * @param string $post_name the post name
     * @param string $type the type of the content
     * @param bool $active if the node is active and in the list or not
     * @param string $caption the caption of the node
     */
    function __construct($list = null, $pid, $post_name, $type, $active, $caption){
        $this->list = $list;
        $this->pid = $pid;
        $this->post_name = $post_name;
        $this->type = $type;
        $this->active = $active;
        $this->caption = $caption;
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
        $out[$this->pid] = $this->getNodeAsArray();
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
        $out = $this->getNodeAsArray();
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
     * Returns the node as array
     * @return array
     */
    function getNodeAsArray(){
        $out = array();
        $out["id"] = $this->pid;
        $out["pid"] = $this->pid;
        $out["type"] = $this->type;
        $out["numberArray"] = array();
        $out["onGoingNumber"] = '';
        $out["caption"] = $this->caption;
        $out["active"] = $this->active;
        return $out;
    }

    /**
     * Returns a node by a id
     * @param string $id id of the node
     * @return \PressBooks\Lists\ListNode|false
     */
    function getNodeById($id){
        if($id == $this->post_name || $id == "p-".$this->pid){
            return $this;
        }
        foreach($this->child as $child){
            if($n = $child->getNodeById($id)){
                return $n;
            }
        }
        return false;
    }

} 