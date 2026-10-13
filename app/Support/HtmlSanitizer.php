<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Allow-list sanitizer for rich-text editor (Summernote) output.
 * Keeps basic formatting, lists, tables and links; drops scripts, styles,
 * event handlers, inline styles and javascript:/data: URLs.
 */
class HtmlSanitizer
{
    private const ALLOWED_TAGS = [
        'p', 'br', 'div', 'span', 'b', 'strong', 'i', 'em', 'u', 's', 'strike', 'sub', 'sup',
        'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'pre', 'code', 'hr',
        'a', 'img', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th',
    ];

    /** Elements removed together with everything inside them. */
    private const DROP_WITH_CONTENT = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'svg', 'math', 'head', 'title', 'meta', 'link'];

    private const ALLOWED_ATTRS = [
        'a' => ['href', 'title', 'target'],
        'img' => ['src', 'alt', 'title', 'width', 'height'],
        'td' => ['colspan', 'rowspan'],
        'th' => ['colspan', 'rowspan'],
    ];

    public static function clean(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $html = trim($html);
        if ($html === '' || trim(strip_tags($html, '<img>')) === '' ) {
            return null;
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $doc->getElementById('__root');
        if (!$root) {
            return e(strip_tags($html));
        }

        self::cleanChildren($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        $out = trim($out);

        return $out === '' ? null : $out;
    }

    private static function cleanChildren(DOMNode $node): void
    {
        // Copy first: we mutate the list while walking it.
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                $node->removeChild($child);
                continue;
            }

            if (!$child instanceof DOMElement) {
                continue; // text nodes are fine
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                $node->removeChild($child);
                continue;
            }

            self::cleanChildren($child);

            if (!in_array($tag, self::ALLOWED_TAGS, true)) {
                // Unwrap: keep the text, drop the tag.
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }

            $allowed = self::ALLOWED_ATTRS[$tag] ?? [];
            foreach (iterator_to_array($child->attributes) as $attr) {
                $name = strtolower($attr->name);
                if (!in_array($name, $allowed, true)) {
                    $child->removeAttribute($attr->name);
                    continue;
                }
                if (in_array($name, ['href', 'src'], true) && !self::isSafeUrl($attr->value)) {
                    $child->removeAttribute($attr->name);
                }
            }

            if ($tag === 'a' && $child->hasAttribute('href')) {
                $child->setAttribute('target', '_blank');
                $child->setAttribute('rel', 'noopener noreferrer');
            }
            if ($tag === 'img' && !$child->hasAttribute('src')) {
                $node->removeChild($child);
            }
        }
    }

    private static function isSafeUrl(string $url): bool
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5));
        $compact = strtolower(preg_replace('/[\x00-\x20]+/', '', $url));

        if ($compact === '' || str_starts_with($compact, '#') || str_starts_with($compact, '/')) {
            return true;
        }

        return (bool) preg_match('#^(https?:|mailto:|tel:)#', $compact);
    }
}
