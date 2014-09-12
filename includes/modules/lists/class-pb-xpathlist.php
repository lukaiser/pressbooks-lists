<?php
/**
 * Created by PhpStorm.
 * User: lukas
 * Date: 10.07.14
 * Time: 09:04
 */

namespace PressBooks\Lists;


/**
 * Class XpathList
 * A List that acquires data by xPath
 * @package PressBooks\Lists
 */
class XpathList implements iList {

    /**
     * @var string|array the HTML tag name or tag names
     */
    private $tagname;
    /**
     * @var string the Xpath from the DOMElement to the caption
     */
    private $captionXpath;
    /**
     * @var array the chapters
     */
    public $chapters;

    /**
     * @param string|array $tagname the HTML tag name or tag names
     * @param string $captionXpath the Xpath from the DOMElement to the caption
     */
    function __construct($tagname, $captionXpath)
    {
        $this->tagname = $tagname;
        $this->captionXpath = $captionXpath;
        $this->chapters = array();
    }

    /**
     * Add a the content of a post to the list
     * @param string $content the content of the post
     * @param int $pid the id of the post
     * @param string $type the type of the content
     */
    function addContentToList($content, $pid, $type)
    {
        $c = new \PressBooks\Lists\ListChapter($this, $pid, $type);
        $this->chapters[] = $c;

        if(trim($content) != "" && $type != "part"){

            libxml_use_internal_errors( true );

            $html = new \DOMDocument();
            $content = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
            $html->loadHTML("<div>".$content."</div>", LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            $xpath = new \DOMXpath($html);
            $ss = $this->getSearchXpath();
            foreach( $xpath->query($ss) as $node ) {
                $newn = $this->createListNodeWithNode($node, $xpath, $pid);
                $c->addChild($newn);
            }

            $errors = libxml_get_errors(); // TODO: Handle errors gracefully
            libxml_clear_errors();
        }
    }

    /**
     * Adds missing html ids and in-list and not-in-list classes to a post
     * @param int $pid the post id
     * @return string|false
     */
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
                    $nclassa[] = "in-list";
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

    /**
     * Change the type of a node e.g. h1 to h4
     * @param string $id id of the node
     * @param string $type the new type
     */
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

    /**
     * Changes the in list status of the node
     * @param string $id id of the node
     * @param boolean $active the status the node should become
     */
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

    /**
     * Returns an array of nodes.
     * All nodes are children of the array
     * Active and Inactive ones
     * @return array
     */
    function getFlatArray()
    {
        $out = array();
        foreach($this->chapters as $list){
            $out = array_merge($out, $list->getFlatArray());
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
        foreach($this->chapters as $list){
            $out = array_merge($out, $list->getFlatArrayWithChapter());
        }
        return($out);
    }

    /**
     * Returns an array representing the hierarchy of the nodes
     * Chapters are represented too
     * Only active nodes
     * @return array
     */
    function getHierarchicalArray()
    {
        $out = array();
        foreach($this->chapters as $list){
            $out[] = $list->getHierarchicalArray();
        }
        return($out);
    }

    /**
     * Returns a ongoing numbers representing the position of the child
     * @param \PressBooks\Lists\ListNode $child
     * @return int
     */
    function getOnGoingNumberOfChild($child){
        $i = 0;
        foreach($this->chapters as $chapter){
            $n = $chapter->getOnGoingNumberOfChild($child);
            if($n > 0){
                return($i+$n);
            }else{
                $i -= $n;
            }
        }
        return(0);
    }

    /**
     * Returns all the types the list represents
     * @return array|string
     */
    function getTypes(){
        return($this->tagname);
    }

    /**
     * Returns the hierarchy level of a tagname
     * @param string $tagname
     * @return int
     */
    function getDepthOfTagname($tagname){
        if(is_array($this->tagname)){
            foreach($this->tagname as $i){
                return(array_search($tagname, $this->tagname));
            }
        }else{
            return(0);
        }
    }

    /**
     * Returns a node by a id
     * @param string $id id of the node
     * @return \PressBooks\Lists\ListNode|false
     */
    function getNodeById($id){
        foreach($this->chapters as $list){
            if($n = $list->getNodeById($id)){
                return $n;
            }
        }
        return false;
    }

    /**
     * Adds the prefix the captions of the content
     * @param string $content the content the captions should be added to
     * @return string
     */
    function addCaptionPrefix($content){
        if(trim($content) != ""){

            $up = new \PressBooks\Lists\DOMElementUpdater();
            $xpath = $up->getDOMXpath($content);
            $ss = $this->getSearchXpath();
            foreach( $xpath->query($ss) as $node ) {
                $id = $node->getAttribute("id");

                if($id){
                    $ndata = $this->getNodeById($id);
                    if($ndata && $ndata->active){
                        $prefix = ListNodeShow::get_caption_prefix($ndata);
                        $this->addCaptionPrefixToNode($xpath, $node, $ndata, $prefix);
                    }
                }
            }


            $content = $up->getContent();
        }
        return $content;
    }

    /**
     * Get the ListChapter by the PID
     * @param int $pid the PID
     * @return \PressBooks\Lists\ListChapter|false
     */
    function getChapterByPid($pid){
        foreach($this->chapters as $c){
            if($pid == $c->pid){
                return($c);
            }
        }
        return false;
    }

    /**
     * Returns the xPaht for the tagname or tagnames
     * @return string
     */
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

    /**
     * Creates a ListNode from a DOMElement
     * @param DOMElement $node the node
     * @param DOMXpath $xpath the xPath object
     * @param int $pid the post id
     * @return ListNode
     */
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

    /**
     * Adds the caption prefix to the node
     * @param DOMXpath $xpath the Xpath object
     * @param DOMElement $node the HTML Element
     * @param ListNode $ndata the meta data
     * @param string $prefix the prefix
     */
    protected function addCaptionPrefixToNode($xpath, $node, $ndata, $prefix){
        $c = $xpath->query($this->captionXpath, $node)->item(0);
        if(get_class($c) == "DOMAttr"){
            $c->value = $prefix.$c->value;
        }else if(get_class($c) == "DOMElement"){
            $c->nodeValue = $prefix.$c->nodeValue;
        }
    }

}