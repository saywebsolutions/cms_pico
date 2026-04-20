<?php

namespace OCA\CMSPico\Pico;

use DOMDocument;
use DOMElement;
use ParsedownExtra;

/**
 * Extended ParsedownExtra with PHP 8.1+ compatibility fix.
 *
 * Fixes ValueError when DOMDocument::loadHTML() receives empty string.
 */
class SafeParsedownExtra extends ParsedownExtra
{
    /**
     * Process HTML tag with empty markup guard for PHP 8.1+ compatibility.
     *
     * @param string $elementMarkup
     * @return string
     */
    protected function processTag($elementMarkup)
    {
        libxml_use_internal_errors(true);

        $DOMDocument = new DOMDocument;

        $elementMarkup = mb_convert_encoding($elementMarkup, 'HTML-ENTITIES', 'UTF-8');

        // Guard against empty markup (PHP 8.1+ throws ValueError)
        if ($elementMarkup === '' || $elementMarkup === false) {
            return '';
        }

        $DOMDocument->loadHTML($elementMarkup);
        $DOMDocument->removeChild($DOMDocument->doctype);
        $DOMDocument->replaceChild($DOMDocument->firstChild->firstChild->firstChild, $DOMDocument->firstChild);

        $elementText = '';

        if ($DOMDocument->documentElement->getAttribute('markdown') === '1') {
            foreach ($DOMDocument->documentElement->childNodes as $Node) {
                $elementText .= $DOMDocument->saveHTML($Node);
            }

            $DOMDocument->documentElement->removeAttribute('markdown');

            $elementText = "\n" . $this->text($elementText) . "\n";
        } else {
            foreach ($DOMDocument->documentElement->childNodes as $Node) {
                $nodeMarkup = $DOMDocument->saveHTML($Node);

                if ($Node instanceof DOMElement and !in_array($Node->nodeName, $this->textLevelElements)) {
                    $elementText .= $this->processTag($nodeMarkup);
                } else {
                    $elementText .= $nodeMarkup;
                }
            }
        }

        $DOMDocument->documentElement->nodeValue = 'placeholder\x1A';

        $markup = $DOMDocument->saveHTML($DOMDocument->documentElement);
        $markup = str_replace('placeholder\x1A', $elementText, $markup);

        return $markup;
    }
}
