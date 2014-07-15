<?php
/**
 * Created by PhpStorm.
 * User: lukas
 * Date: 10.07.14
 * Time: 08:58
 */

namespace PressBooks\Lists;


/**
 * Interface iList
 * A interface for all type of list that can exist and be added to Lists::get_Initial_Lists
 * @package PressBooks\Lists
 */
interface iList {
    /**
     * Add a the content of a post to the list
     * @param string $content the content of the post
     * @param int $pid the id of the post
     */
    function addContentToList($content, $pid);

    /**
     * Adds missing html ids and in-list and not-in-list classes to a post
     * @param int $pid the post id
     * @return string|false
     */
    function contentAddMissingIdAndClasses($pid);

    /**
     * Change the type of a node e.g. h1 to h4
     * @param string $id id of the node
     * @param string $type the new type
     */
    function changeNodeType($id, $type);

    /**
     * Changes the in list status of the node
     * @param string $id id of the node
     * @param boolean $active the status the node should become
     */
    function setNodeActive($id, $active);

    /**
     * Returns an array of nodes.
     * All nodes are children of the array
     * Active and Inactive ones
     * @return array
     */
    function getFlatArray();

    /**
     * Returns an array representing the hierarchy of the nodes
     * Chapters are represented too
     * Only active nodes
     * @return array
     */
    function getHierarchicalArray();

    /**
     * Returns all the types the list represents
     * @return array|string
     */
    function getTypes();

    /**
     * Returns a node by a id
     * @param string $id id of the node
     * @return \PressBooks\Lists\ListNode|false
     */
    function getNodeById($id);

    /**
     * Adds the prefix the captions of the content
     * @param string $content the content the captions should be added to
     * @return string
     */
    function addCaptionPrefix($content);
} 