<?php
/**
 * Created by PhpStorm.
 * User: lukas
 * Date: 08.07.14
 * Time: 09:55
 */

namespace PressBooks\Lists;


/**
 * Class ListNode
 * A class representing a node in a list
 * @package PressBooks\Lists
 */
class ListNode {

    /**
     * @var \PressBooks\Lists\ListChapter the chapter the node is in
     */
    public $chapter;
    /**
     * @var \PressBooks\Lists\iList the list the node is in
     */
    public $list;

    /**
     * @var bool if the node is active and in the list or not
     */
    public $active;
    /**
     * @var int the id of the post the node is from
     */
    public $pid;
    /**
     * @var string the id of the node
     */
    public $id;
    /**
     * @var string the type of the node
     */
    public $type;
    /**
     * @var string the caption of the node
     */
    public $caption;

    /**
     * @param \PressBooks\Lists\ListChapter the chapter the node is in $list
     * @param bool $active if the node is active and in the list or not
     * @param int $pid the id of the post the node is from
     * @param string $id the id of the node
     * @param string $type the type of the node
     * @param string $caption the caption of the node
     */
    function __construct($list = null, $active = true, $pid = null, $id = null, $type = null, $caption = null){
        $this->list = $list;
        $this->active = $active;
        $this->pid = $pid;
        $this->id = $id;
        $this->type = $type;
        $this->caption = $caption;
    }

    /**
     * Returns an array of the numbers representing the position of the node in the content
     * @return array
     */
    function getNumbering(){
        if($this->active){
            return($this->chapter->getNumberingOfChild($this));
        }else{
            return(array());
        }
    }

    /**
     * Returns the node as array
     * @return array
     */
    function getNodeAsArray(){
        $out = array();
        $out["id"] = $this->id;
        $out["type"] = $this->type;
        $out["number"] = $this->number;
        $out["numberArray"] = $this->getNumbering();
        $out["caption"] = $this->caption;
        $out["active"] = $this->active;
        return $out;
    }

    /**
     * Returns it self if the id is the one of the node
     * @param string $id
     * @return $this|false
     */
    function getNodeById($id){
        if($id == $this->id){
            return $this;
        }
        return false;
    }

    /**
     * @param $property
     * @return string
     */
    function __get($property) {

        if($property == "number"){
            return implode(".", $this->getNumbering());
        }

        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }

} 