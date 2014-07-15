<?php
/**
 * Created by PhpStorm.
 * User: lukas
 * Date: 14.07.14
 * Time: 17:44
 */

namespace PressBooks\Lists;


class ImgList extends XpathList {
    protected function addCaptionPrefixToNode($xpath, $node, $ndata, $prefix)
    {
        parent::addCaptionPrefixToNode($xpath, $node, $ndata, $prefix);
        $c = $xpath->query("ancestor::div[contains(concat(' ', @class, ' '), 'wp-caption')]/p[contains(concat(' ', @class, ' '), 'wp-caption-text')]", $node)->item(0);
        if(get_class($c) == "DOMElement"){
            $c->nodeValue = $prefix.$c->nodeValue;
        }
    }

} 