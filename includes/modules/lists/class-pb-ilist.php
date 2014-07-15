<?php
/**
 * Created by PhpStorm.
 * User: lukas
 * Date: 10.07.14
 * Time: 08:58
 */

namespace PressBooks\Lists;


interface iList {
    function addContentToList($content, $pid);
    function contentAddMissingIdAndClasses($pid);
    function changeNodeType($id, $type);
    function setNodeActive($id, $active);
    function getFlatArray();
    function getHierarchicalArray();
    function getTypes();
    function getNodeById($id);
    function addCaptionPrefix($content);
} 