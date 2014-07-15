<?php
/**
 * Created by PhpStorm.
 * User: lukas
 * Date: 08.07.14
 * Time: 09:55
 */

namespace PressBooks\Lists;


class ListChapter {

    public $list;

    public $number;
    public $child;

    function __construct($list = null, $number){
        $this->list = $list;
        $this->number = $number;
        $this->child = array();
    }

    function addChild($child){
        $child->chapter = $this;
        $this->child[] = $child;
    }

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

    function getFlatArray(){
        $out = array();
        foreach($this->child as $child){
            $out[$child->id] = $child->getNodeAsArray();
        }
        return($out);
    }

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

    function getNodeById($id){
        foreach($this->child as $child){
            if($n = $child->getNodeById($id)){
                return $n;
            }
        }
        return false;
    }

} 