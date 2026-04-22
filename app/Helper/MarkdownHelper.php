<?php

namespace App\Helpers;

/**
 * Advanced Markdown to HTML Parser
 * Use this if you need more sophisticated markdown parsing
 */
class MarkdownHelper
{
    /**
     * Convert markdown to HTML with advanced features
     */
    public static function parse(string $markdown): string
    {
        $html = trim($markdown);

        // Headers (must be done before other processing)
        $html = self::parseHeaders($html);

        // Bold and Italic (order matters)
        $html = self::parseBoldItalic($html);

        // Lists
        $html = self::parseLists($html);

        // Blockquotes
        $html = self::parseBlockquotes($html);

        // Code blocks
        $html = self::parseCodeBlocks($html);

        // Inline code
        $html = self::parseInlineCode($html);

        // Links
        $html = self::parseLinks($html);

        // Line breaks and paragraphs
        $html = self::parseLineBreaks($html);

        // Clean up
        $html = self::cleanup($html);

        return $html;
    }

    /**
     * Parse headers (# to ######)
     */
    protected static function parseHeaders(string $text): string
    {
        // H1 to H6
        $text = preg_replace('/^######\s+(.+)$/m', '<h6>$1</h6>', $text);
        $text = preg_replace('/^#####\s+(.+)$/m', '<h5>$1</h5>', $text);
        $text = preg_replace('/^####\s+(.+)$/m', '<h4>$1</h4>', $text);
        $text = preg_replace('/^###\s+(.+)$/m', '<h3>$1</h3>', $text);
        $text = preg_replace('/^##\s+(.+)$/m', '<h2>$1</h2>', $text);
        $text = preg_replace('/^#\s+(.+)$/m', '<h1>$1</h1>', $text);

        return $text;
    }

    /**
     * Parse bold and italic text
     */
    protected static function parseBoldItalic(string $text): string
    {
        // Bold with **text** or __text__
        $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text);
        $text = preg_replace('/__(.+?)__/s', '<strong>$1</strong>', $text);

        // Italic with *text* or _text_
        $text = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/s', '<em>$1</em>', $text);
        $text = preg_replace('/(?<!_)_(?!_)(.+?)(?<!_)_(?!_)/s', '<em>$1</em>', $text);

        return $text;
    }

    /**
     * Parse ordered and unordered lists
     */
    protected static function parseLists(string $text): string
    {
        // Split text into lines for processing
        $lines = explode("\n", $text);
        $result = [];
        $inOrderedList = false;
        $inUnorderedList = false;

        foreach ($lines as $line) {
            // Ordered list (1. 2. 3.)
            if (preg_match('/^\d+\.\s+(.+)$/', $line, $matches)) {
                if (!$inOrderedList) {
                    $result[] = '<ol>';
                    $inOrderedList = true;
                }
                if ($inUnorderedList) {
                    $result[] = '</ul>';
                    $inUnorderedList = false;
                }
                $result[] = '<li>' . $matches[1] . '</li>';
            }
            // Unordered list (-, *, +)
            elseif (preg_match('/^[\-\*\+]\s+(.+)$/', $line, $matches)) {
                if (!$inUnorderedList) {
                    $result[] = '<ul>';
                    $inUnorderedList = true;
                }
                if ($inOrderedList) {
                    $result[] = '</ol>';
                    $inOrderedList = false;
                }
                $result[] = '<li>' . $matches[1] . '</li>';
            }
            // Regular line
            else {
                if ($inOrderedList) {
                    $result[] = '</ol>';
                    $inOrderedList = false;
                }
                if ($inUnorderedList) {
                    $result[] = '</ul>';
                    $inUnorderedList = false;
                }
                $result[] = $line;
            }
        }

        // Close any open lists
        if ($inOrderedList) {
            $result[] = '</ol>';
        }
        if ($inUnorderedList) {
            $result[] = '</ul>';
        }

        return implode("\n", $result);
    }

    /**
     * Parse blockquotes
     */
    protected static function parseBlockquotes(string $text): string
    {
        $lines = explode("\n", $text);
        $result = [];
        $inBlockquote = false;

        foreach ($lines as $line) {
            if (preg_match('/^>\s+(.+)$/', $line, $matches)) {
                if (!$inBlockquote) {
                    $result[] = '<blockquote>';
                    $inBlockquote = true;
                }
                $result[] = $matches[1] . '<br>';
            } else {
                if ($inBlockquote) {
                    $result[] = '</blockquote>';
                    $inBlockquote = false;
                }
                $result[] = $line;
            }
        }

        if ($inBlockquote) {
            $result[] = '</blockquote>';
        }

        return implode("\n", $result);
    }

    /**
     * Parse code blocks (```code```)
     */
    protected static function parseCodeBlocks(string $text): string
    {
        // Fenced code blocks with language
        $text = preg_replace_callback('/```(\w+)?\n(.*?)```/s', function($matches) {
            $language = $matches[1] ?? '';
            $code = htmlspecialchars($matches[2], ENT_QUOTES, 'UTF-8');
            $langClass = $language ? ' class="language-' . $language . '"' : '';
            return '<pre><code' . $langClass . '>' . $code . '</code></pre>';
        }, $text);

        return $text;
    }

    /**
     * Parse inline code (`code`)
     */
    protected static function parseInlineCode(string $text): string
    {
        $text = preg_replace('/`(.+?)`/', '<code>$1</code>', $text);
        return $text;
    }

    /**
     * Parse links [text](url)
     */
    protected static function parseLinks(string $text): string
    {
        $text = preg_replace('/\[(.+?)\]\((.+?)\)/', '<a href="$2" target="_blank">$1</a>', $text);
        return $text;
    }

    /**
     * Parse line breaks and paragraphs
     */
    protected static function parseLineBreaks(string $text): string
    {
        // Don't add breaks after block elements
        $text = preg_replace('/(?<!>)\n(?!<[\/]?(h[1-6]|ul|ol|li|blockquote|pre|code)>)/', '<br>', $text);

        // Remove breaks around block elements
        $text = preg_replace('/<br>\s*(<\/?(?:h[1-6]|ul|ol|li|blockquote|pre)>)/', '$1', $text);
        $text = preg_replace('/(<\/?(?:h[1-6]|ul|ol|li|blockquote|pre)>)\s*<br>/', '$1', $text);

        // Remove multiple consecutive breaks
        $text = preg_replace('/(<br\s*\/?>\s*){3,}/', '<br><br>', $text);

        return $text;
    }

    /**
     * Cleanup HTML
     */
    protected static function cleanup(string $text): string
    {
        // Remove extra whitespace
        $text = preg_replace('/[ \t]+/', ' ', $text);

        // Remove whitespace before closing tags
        $text = preg_replace('/\s+<\//', '</', $text);

        // Trim
        $text = trim($text);

        return $text;
    }

    /**
     * Strip all HTML tags (useful for plain text extraction)
     */
    public static function toPlainText(string $html): string
    {
        return strip_tags($html);
    }

    /**
     * Convert HTML back to markdown (basic)
     */
    public static function toMarkdown(string $html): string
    {
        $markdown = $html;

        // Headers
        $markdown = preg_replace('/<h1>(.+?)<\/h1>/', '# $1', $markdown);
        $markdown = preg_replace('/<h2>(.+?)<\/h2>/', '## $1', $markdown);
        $markdown = preg_replace('/<h3>(.+?)<\/h3>/', '### $1', $markdown);

        // Bold and italic
        $markdown = preg_replace('/<strong>(.+?)<\/strong>/', '**$1**', $markdown);
        $markdown = preg_replace('/<em>(.+?)<\/em>/', '*$1*', $markdown);

        // Lists
        $markdown = preg_replace('/<li>(.+?)<\/li>/', '- $1', $markdown);
        $markdown = str_replace(['<ul>', '</ul>', '<ol>', '</ol>'], '', $markdown);

        // Links
        $markdown = preg_replace('/<a href="(.+?)">(.+?)<\/a>/', '[$2]($1)', $markdown);

        // Line breaks
        $markdown = str_replace(['<br>', '<br/>'], "\n", $markdown);

        // Strip remaining tags
        $markdown = strip_tags($markdown);

        return $markdown;
    }
}
