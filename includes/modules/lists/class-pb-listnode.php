<?php
/**
 * Created by PhpStorm.
 * User: lukas
 * Date: 08.07.14
 * Time: 09:55
 */

namespace PressBooks\Lists;


class ListNode {

    public $chapter;
    public $list;

    public $active;
    public $pid;
    public $id;
    public $type;
    public $caption;

    function __construct($list = null, $active = true, $pid = null, $id = null, $type = null, $caption = null){
        $this->list = $list;
        $this->active = $active;
        $this->pid = $pid;
        $this->id = $id;
        $this->type = $type;
        $this->caption = $caption;
    }

    function getNumbering(){
        if($this->active){
            return($this->chapter->getNumberingOfChild($this));
        }else{
            return(array());
        }
    }

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

    function getNodeById($id){
        if($id == $this->id){
            return $this;
        }
        return false;
    }

    function __get($property) {

        if($property == "number"){
            return implode(".", $this->getNumbering());
        }

        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }

} 