<?php

namespace App\Services\ProductImport;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Product descriptions from a spreadsheet cell.
 *
 * Plain text becomes simple HTML: a blank line starts a new paragraph, lines starting with "- ",
 * "• " or "* " become a bullet list, and a line ending with ":" becomes a bold heading.
 * HTML is accepted but cleaned against an allow-list (no scripts, styles, event handlers or
 * javascript: links); every image must keep alt text, as the product form requires.
 */
class HtmlDescription
{
    private const ALLOWED = [
        'p' => [], 'br' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [],
        'ul' => [], 'ol' => [], 'li' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'h5' => [], 'blockquote' => [],
        'a' => ['href', 'title'], 'img' => ['src', 'alt', 'width', 'height'],
        'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => ['colspan', 'rowspan'], 'td' => ['colspan', 'rowspan'],
        'span' => [], 'div' => [],
    ];

    /** Removed together with their content. */
    private const DROP = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'select', 'textarea', 'svg', 'math', 'noscript', 'template', 'link', 'meta', 'head', 'title'];

    public static function looksLikeHtml(string $text): bool
    {
        return (bool) preg_match('/<\s*\/?\s*(p|br|ul|ol|li|strong|b|em|i|u|h[1-6]|div|span|a|img|table|blockquote)\b[^>]*>/i', $text);
    }

    /** Clean HTML for the description field; $altFallback fills <img> tags without alt text. */
    public static function fromCell(string $text, string $altFallback): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        return self::looksLikeHtml($text) ? self::sanitize($text, $altFallback) : self::fromPlainText($text);
    }

    public static function fromPlainText(string $text): string
    {
        $html = [];
        foreach (preg_split('/\n\s*\n/u', str_replace(["\r\n", "\r"], "\n", trim($text))) as $block) {
            $lines = array_values(array_filter(array_map('trim', explode("\n", $block)), fn ($l) => $l !== ''));
            $paragraph = [];
            $items = [];
            $flushParagraph = function () use (&$paragraph, &$html) {
                if ($paragraph) {
                    $html[] = '<p>' . implode('<br>', $paragraph) . '</p>';
                    $paragraph = [];
                }
            };
            $flushList = function () use (&$items, &$html) {
                if ($items) {
                    $html[] = '<ul>' . implode('', array_map(fn ($i) => "<li>{$i}</li>", $items)) . '</ul>';
                    $items = [];
                }
            };

            foreach ($lines as $line) {
                if (preg_match('/^(?:[-•*▪●]\s+)(.+)$/u', $line, $m)) {
                    $flushParagraph();
                    $items[] = e(trim($m[1]));
                } elseif (mb_substr($line, -1) === ':' && mb_strlen($line) <= 80) {
                    $flushParagraph();
                    $flushList();
                    $html[] = '<p><strong>' . e($line) . '</strong></p>';
                } else {
                    $flushList();
                    $paragraph[] = e($line);
                }
            }
            $flushParagraph();
            $flushList();
        }

        return implode("\n", $html);
    }

    public static function sanitize(string $html, string $altFallback): string
    {
        $doc = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div id="bg-root">' . $html . '</div>', LIBXML_NONET | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $doc->getElementById('bg-root');
        if (!$root) {
            return e(strip_tags($html));
        }
        self::clean($root, $altFallback);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return trim($out);
    }

    private static function clean(DOMNode $node, string $altFallback): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                $node->removeChild($child);
                continue;
            }
            if (!$child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->nodeName);
            if (in_array($tag, self::DROP, true)) {
                $node->removeChild($child);
                continue;
            }

            self::clean($child, $altFallback);

            if (!array_key_exists($tag, self::ALLOWED)) {
                // Unknown tag: keep its text, drop the tag.
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }

            foreach (iterator_to_array($child->attributes) as $attribute) {
                if (!in_array(strtolower($attribute->nodeName), self::ALLOWED[$tag], true)) {
                    $child->removeAttribute($attribute->nodeName);
                }
            }

            if ($tag === 'a') {
                if (!self::safeUrl($child->getAttribute('href'), true)) {
                    $child->removeAttribute('href');
                } else {
                    $child->setAttribute('rel', 'nofollow noopener');
                }
            }
            if ($tag === 'img') {
                if (!self::safeUrl($child->getAttribute('src'), false)) {
                    $node->removeChild($child);
                    continue;
                }
                if (trim($child->getAttribute('alt')) === '') {
                    $child->setAttribute('alt', $altFallback);
                }
            }
        }
    }

    private static function safeUrl(string $url, bool $allowMail): bool
    {
        $url = trim(html_entity_decode($url));

        return (bool) preg_match($allowMail ? '#^(https?://|mailto:|/)#i' : '#^(https?://|/)#i', $url)
            && !preg_match('#^//#', $url);
    }
}
