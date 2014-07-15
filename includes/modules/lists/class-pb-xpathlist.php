<?php
/**
 * Created by PhpStorm.
 * User: lukas
 * Date: 10.07.14
 * Time: 09:04
 */

namespace PressBooks\Lists;


class XpathList implements iList {

    private $tagname;
    private $captionXpath;
    public $list;

    function __construct($tagname, $captionXpath)
    {
        $this->tagname = $tagname;
        $this->captionXpath = $captionXpath;
        $this->list = array();
    }

    function addContentToList($content, $pid)
    {
        $post = get_post($pid);
        $cn = pb_get_chapter_number($post->post_name);
        $c = new \PressBooks\Lists\ListChapter($this, $cn);
        $this->list[] = $c;

        if(trim($content) != ""){
            $html = new \DOMDocument();
            $content = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
            $html->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            $xpath = new \DOMXpath($html);
            $ss = $this->getSearchXpath();
            foreach( $xpath->query($ss) as $node ) {
                $newn = $this->createListNodeWithNode($node, $xpath, $pid);
                $c->addChild($newn);
            }
        }
    }
    function contentAddMissingIdAndClasses($content){
        $changed = false;
        if(trim($content) != ""){
            $up = new \PressBooks\Lists\DOMElementUpdater();
            $xpath = $up->getDOMXpath($content);
            $ss = $this->getSearchXpath();

            foreach( $xpath->query($ss) as $node ) {

                if(trim($node->getAttribute("id")) == ""){
                    $changed = true;
                    $node->setAttribute("id", substr( md5(rand()), 0, 11));
                }

                $nclass = $node->getAttribute("class");
                $nclassa = explode(" ", $nclass);
                if(!in_array("in-list",$nclassa) && !in_array("not-in-list",$nclassa)){
                    $changed = true;
                    $nclassa[] = "in-list"; //TODO default
                    $node->setAttribute("class", implode(" ", array_filter($nclassa)));
                }
            }

            $content = $up->getContent();
        }
        if($changed){
            return($content);
        }else{
            return(false);
        }
    }

    function changeNodeType($id, $type){
        if(is_array($this->tagname) && in_array($type, $this->tagname)){

            $node = $this->getNodeById($id);

            if($node->type != $type){

                $up = new \PressBooks\Lists\DOMElementUpdater();
                $hnode = $up->getDomElement($node);

                if($hnode) {
                    $newnode = $hnode->ownerDocument->createElement($type);
                    foreach ($hnode->childNodes as $child){
                        //$child = $hnode->ownerDocument->importNode($child, true);
                        $child = $child->cloneNode(true);
                        $newnode->appendChild($child);
                    }
                    $atts = array();
                    foreach ($hnode->attributes as $attrNode) {
                        $atts[] = $attrNode;
                    }
                    foreach($atts as $attrNode){
                        $newnode->setAttributeNode($attrNode);
                    }
                    $hnode->parentNode->replaceChild($newnode, $hnode);
                }

                $up->save();

                $node->type = $type;
            }
        }

    }
    function setNodeActive($id, $active){

        $node = $this->getNodeById($id);
        $up = new \PressBooks\Lists\DOMElementUpdater();
        $hnode = $up->getDomElement($node);
        if($hnode) {
            $nclass = $hnode->getAttribute("class");
            $nclassa = explode(" ", $nclass);
            if($active){
                if(!in_array("in-list",$nclassa)){
                    $nclassa[] = "in-list";
                }
                if(($key = array_search("not-in-list", $nclassa)) !== false) {
                    unset($nclassa[$key]);
                }
            }else{
                if(!in_array("not-in-list",$nclassa)){
                    $nclassa[] = "not-in-list";
                }
                if(($key = array_search("in-list", $nclassa)) !== false) {
                    unset($nclassa[$key]);
                }
            }
            $hnode->setAttribute("class", implode(" ", array_filter($nclassa)));
        }

        $up->save();
        $node->active = $active;
    }

    function getFlatArray()
    {
        $out = array();
        foreach($this->list as $list){
            $out = array_merge($out, $list->getFlatArray());
        }
        return($out);
    }

    function getHierarchicalArray()
    {
        $out = array();
        foreach($this->list as $list){
            $out[] = $list->getHierarchicalArray();
        }
        return($out);
    }

    function getTypes(){
        return($this->tagname);
    }

    function getDepthOfTagname($tagname){
        if(is_array($this->tagname)){
            foreach($this->tagname as $i){
                return(array_search($tagname, $this->tagname));
            }
        }else{
            return(0);
        }
    }

    function getNodeById($id){
        foreach($this->list as $list){
            if($n = $list->getNodeById($id)){
                return $n;
            }
        }
        return false;
    }

    function addCaptionPrefix($content){
        if(trim($content) != ""){
            $up = new \PressBooks\Lists\DOMElementUpdater();
            $xpath = $up->getDOMXpath($content);
            $ss = $this->getSearchXpath();

            foreach( $xpath->query($ss) as $node ) {
                $id = $node->getAttribute("id");
                $nclass = $node->getAttribute("class");
                $nclassa = explode(" ", $nclass);

                if(in_array("in-list",$nclassa) && $id){
                    $ndata = $this->getNodeById($id);
                    $prefix = ListNodeShow::getCaptionPrefix($ndata);
                    $this->addCaptionPrefixToNode($xpath, $node, $ndata, $prefix);
                }
            }


            $content = $up->getContent();
        }
        return $content;
    }

    private function getSearchXpath(){
        $ssa = array();
        if(is_array($this->tagname)){
            foreach($this->tagname as $i){
                $ssa[] = "self::".$i;
            }
        }else{
            $ssa[] = "self::".$this->tagname;
        }

        $ss = implode(" or ", $ssa);
        return "//*[".$ss."]";
    }

    private function createListNodeWithNode($node, $xpath, $pid){
        $nname = $node->nodeName;
        $nclass = $node->getAttribute("class");
        $nclassa = explode(" ", $nclass);
        $active = in_array("in-list",$nclassa);
        $nid = $node->getAttribute("id");
        $c = $xpath->query($this->captionXpath, $node)->item(0);
        if(get_class($c) == "DOMAttr"){
            $ncaption = $c->value;
        }else if(get_class($c) == "DOMElement"){
            $ncaption = $c->nodeValue;
        }else{
            $ncaption = "";
        }
        return new \PressBooks\Lists\ListNode($this, $active, $pid, $nid, $nname, $ncaption);
    }

    protected function addCaptionPrefixToNode($xpath, $node, $ndata, $prefix){
        $c = $xpath->query($this->captionXpath, $node)->item(0);
        if(get_class($c) == "DOMAttr"){
            $c->value = $prefix.$c->value;
        }else if(get_class($c) == "DOMElement"){
            $c->nodeValue = $prefix.$c->nodeValue;
        }
    }

}