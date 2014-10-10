<?php
/**
 * Created by PhpStorm.
 * User: lukas
 * Date: 14.07.14
 * Time: 17:44
 */

namespace PressBooks\Lists;


/**
 * Class ImgList
 * The List for Images
 * Basically XpathList but the caption prefix gets added to a second place
 * @package PressBooks\Lists
 */
class ImgList extends XpathList {

    /**
     * Adds the caption prefix to the node
     * @param DOMXpath $xpath the Xpath object
     * @param DOMElement $node the HTML Element
     * @param ListNode $ndata the meta data
     * @param string $prefix the prefix
     * TODO: add functionality to XpathList, so $captionXpath can be a array and all get updated
     */
    protected function addCaptionPrefixToNode($xpath, $node, $ndata, $prefix)
    {
        $prefix2 = ListNodeShow::get_caption_prefix($ndata, true);
        parent::addCaptionPrefixToNode($xpath, $node, $ndata, $prefix2);
        $c = $xpath->query("ancestor::div[contains(concat(' ', @class, ' '), 'wp-caption')]/p[contains(concat(' ', @class, ' '), 'wp-caption-text')]", $node)->item(0);
        if(get_class($c) == "DOMElement"){
            $node = $c->ownerDocument->createElement('span', '');
            $node->nodeValue = $prefix;
            $f = $c->firstChild;
            $c->insertBefore($node, $f);
        }
    }

} 