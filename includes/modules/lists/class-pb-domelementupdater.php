<?php
/**
 * Created by PhpStorm.
 * User: lukas
 * Date: 10.07.14
 * Time: 16:44
 */

namespace PressBooks\Lists;


class DOMElementUpdater {

    private $html;
    private $node;

    function getDomElement($node){
        $this->node = $node;
        $post = get_post($node->pid);
        $xpath = $this->getDOMXpath($post->post_content);
        $ss = $this->getNodeXpath($node);
        return $xpath->query($ss)->item(0);
    }

    function getDOMXpath($content){
        $this->html = new \DOMDocument();
        $content = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
        $this->html->loadHTML("<div>".$content."</div>", LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        return new \DOMXpath($this->html);
    }

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

    function save(){
        $content = $this->getContent();

        $new_post = array(
            'ID'           => $this->node->pid,
            'post_content' => $content
        );
        wp_update_post ($new_post);
    }

    private function getNodeXpath($node){
        return "//".$node->type."[@id='".$node->id."']";
    }
}