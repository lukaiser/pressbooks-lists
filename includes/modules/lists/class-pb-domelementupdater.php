<?php
/**
 * A Class adding functionality to DOMDocument. Used for all xpath the interaction with content
 * @package PressBooks\Lists
 */

namespace PressBooks\Lists;


class DOMElementUpdater {

    /**
     * @var \DOMDocument the DOMDocument
     */
    private $html;
    /**
     * @var \PressBooks\Lists\ListNode the node currently handled
     */
    private $node;


    /**
     * Returns the DOMElement of a node
     * @param \PressBooks\Lists\ListNode $node the node
     * @return \DOMNode
     */
    function getDomElement($node){
        $this->node = $node;
        $post = get_post($node->pid);
        $xpath = $this->getDOMXpath($post->post_content);
        $ss = $this->getNodeXpath($node);
        return $xpath->query($ss)->item(0);
    }


    /**
     * Returns a Xpath object for a content
     * @param string $content the content
     * @return \DOMXpath
     */
    function getDOMXpath($content){
        libxml_use_internal_errors( true );
        $this->html = new \DOMDocument();
        $content = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
        $this->html->loadHTML("<div>".$content."</div>", LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $errors = libxml_get_errors(); // TODO: Handle errors gracefully
        libxml_clear_errors();
        return new \DOMXpath($this->html);
    }


    /**
     * Returns the new content of the DOMDocument generated in getDOMXpath()
     * @return string
     */
    function getContent(){
        $content = $this->html->saveHTML();
        $content = html_entity_decode($content);

        // Remove the opening <div> tag we previously added, if it exists.
        $openDivTag = "<div>";
        if (substr($content, 0, strlen($openDivTag)) == $openDivTag) {
            $content = substr($content, strlen($openDivTag));
        }

        // Remove the closing </div> tag we previously added, if it exists.
        $closeDivTag = "</div>\n";
        $closeChunk = substr($content, -strlen($closeDivTag));
        if ($closeChunk == $closeDivTag) {
            $content = substr($content, 0, -strlen($closeDivTag));
        }
        return $content;
    }


    /**
     * Saves the content of the DOMDocument to the Wordpress Database.
     * Informations from the node passed in getDomElement() is needed
     */
    function save(){
        $content = $this->getContent();

        $new_post = array(
            'ID'           => $this->node->pid,
            'post_content' => $content
        );
        wp_update_post (add_magic_quotes($new_post));
    }


    /**
     * Returns the Xpath needed to find a node in a document
     * @param $node
     * @return string
     */
    private function getNodeXpath($node){
        return "//".$node->type."[@id='".$node->id."']";
    }
}