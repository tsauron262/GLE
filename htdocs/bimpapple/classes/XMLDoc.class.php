<?php
class XMLDoc {

    //--------------------------------------------------------------------------
    static public function checkFilters($Elem, $tagFilter = null, $attrFilter = null) {
        if (!isset($Elem) || !is_a($Elem, 'DOMElement'))
            return false;

        $check = true;
        if (isset($tagFilter)) {
            if ($Elem->tagName != $tagFilter)
                $check = false;
        }
        if ($check) {
            if (isset($attrFilter)) {
                $check = false;
                if ($Elem->hasAttributes()) {
                    if ($Elem->getAttribute($attrFilter['name']) == $attrFilter['value'])
                        $check = true;
                }
            }
        }
        return $check;
    }

    //--------------------------------------------------------------------------
    static public function getElementInnerText($Elem) {
        if (is_a($Elem, 'DOMNode'))
            return $Elem->textContent;

        if (is_array($Elem)) {
            $array = array();
            foreach ($Elem as $item) {
                if (is_a($item, 'DOMNode'))
                    $array[] = $item->textContent;
            }
            return $array;
        }
        return null;
    }

    //--------------------------------------------------------------------------
    static protected function findFilteredChildNodes($parentNode, $tagFilter = null, $attrFilter = null) {
        if (!is_a($parentNode, 'DOMElement'))
            return null;

        if (self::checkFilters($parentNode, $tagFilter, $attrFilter))
            return $parentNode;

        $results = array();
        if ($parentNode->hasChildNodes()) {
            $children = $parentNode->childNodes;
            foreach ($children as $child) {
                $result = self::findFilteredChildNodes($child, $tagFilter, $attrFilter);
                if (isset($result)) {
                    if (is_array($result)) {
                        foreach ($result as $subResult) {
                            $results[] = $subResult;
                        }
                    } else
                        $results[] = $result;
                }
            }
        }

        if (!count($results))
            return null;

        return $results;
    }

    //--------------------------------------------------------------------------
    protected function filterArray($array, $tagFilter = null, $attrFilter = null) {
        if (!isset($array) || !is_array($array))
            return null;

        $results = array();

        foreach ($array as $item) {
            $result = null;
            if (is_array($item)) {
                $result = self::filterArray($item, $tagFilter, $attrFilter);
            } else {
                $result = self::findFilteredChildNodes($item, $tagFilter, $attrFilter);
            }
            if (isset($result)) {
                if (is_array($result)) {
                    foreach ($result as $subResult) {
                        $results[] = $subResult;
                    }
                } else
                    $results[] = $result;
            }
        }

        if (!count($results))
            return null;

        return $results;
    }

    //--------------------------------------------------------------------------
    static public function filterElement($Elem, $tagFilter = null, $attrFilter = null) {
        $result = null;
        if (is_array($Elem)) {
            $result = self::filterArray($Elem);
        } else {
            $result = self::findFilteredChildNodes($Elem, $tagFilter, $attrFilter);
        }
        return $result;
    }

    //--------------------------------------------------------------------------
    static public function findElementByID($doc, $id) {
        if (!is_a($doc, 'DOMDocument'))
            return null;
        return $doc->getElementById($id);
    }

    //--------------------------------------------------------------------------
    static public function findElementsList($doc, $tagName, $attr = null) {
        if (!is_a($doc, 'DOMDocument'))
            return null;

        $list = $doc->getElementsByTagName($tagName);

        if (!isset($list) || ($list->length == 0))
            return null;

        $array = array();

        foreach ($list as $item) {
            $check = true;
            if (isset($attr)) {
                $check = false;
                if (is_a($item, 'DOMElement')) {
                    $itemAttr = $item->getAttribute($attr['name']);
                    if (isset($itemAttr) && ($attr['value'] === $itemAttr))
                        $check = true;
                }
            }
            if ($check)
                $array[] = $item;
        }
        return $array;
    }

    //--------------------------------------------------------------------------
    static public function findChildElements($parent, $tagFilter = null, $attrFilter = null, array $datas = null, $searchDepth = null) {
        if (!is_a($parent, 'DOMElement'))
            return $datas;

        if (!$parent->hasChildNodes())
            return $datas;

        if (!isset($datas))
            $datas = array();

        $children = $parent->childNodes;
        foreach ($children as $child) {
            if (is_a($child, 'DOMElement')) {
                if (self::checkFilters($child, $tagFilter, $attrFilter)) {
                    $datas[] = $child;
                } else {
                    if (!isset($searchDepth) || ($searchDepth > 1)) {
                        if (isset($searchDepth))
                            $searchDepth--;
                        $result = self::findChildElements($child, $tagFilter, $attrFilter, $searchDepth);
                        if (isset($result)) {
                            if (is_array($result)) {
                                foreach ($result as $r)
                                    $datas[] = $r;
                            } else {
                                $datas[] = $result;
                            }
                        }
                    }
                }
            }
        }

        return $datas;
    }

    //--------------------------------------------------------------------------
    static public function findAllChildTextNodes($parent) {
        if (!isset($parent))
            return null;

        if (!is_a($parent, 'DOMNode'))
            return null;

        $results = array();
        if ($parent->nodeType == XML_TEXT_NODE)
            $results[] = $parent->nodeValue;

        if ($parent->hasChildNodes()) {
            $curChild = $parent->firstChild;
            while (isset($curChild)) {
                $subResults = self::findAllChildTextNodes($curChild);
                if (isset($subResults)) {
                    foreach ($subResults as $subResult) {
                        $results[] = $subResult;
                    }
                }
                $curChild = $curChild->nextSibling;
            }
        }

        if (count($results))
            return $results;

        return null;
    }

    //--------------------------------------------------------------------------
}

?>
