<?php

declare(strict_types=1);

namespace Deinte\CookieConsent\Support;

use Deinte\CookieConsent\Enums\ConsentCategory;

/**
 * Rewrites third-party markup so the browser never executes it before consent.
 * Scripts become `type="text/plain"`, iframe and beacon sources move to
 * `data-cc-src`; the runtime restores them once the category is granted.
 */
class HtmlBlocker
{
    /** @param array<int, array{pattern: string, category: string}> $rules */
    public function __construct(protected array $rules = []) {}

    public function block(string $html, ConsentCategory $default): string
    {
        if (trim($html) === '') {
            return $html;
        }

        $html = $this->blockScripts($html, $default);
        $html = $this->blockIframes($html, $default);

        return $this->blockImages($html);
    }

    public function classify(string $urlOrCode): ?ConsentCategory
    {
        foreach ($this->rules as $rule) {
            if (! str_contains($urlOrCode, $rule['pattern'])) {
                continue;
            }

            return ConsentCategory::tryFrom($rule['category']);
        }

        return null;
    }

    protected function blockScripts(string $html, ConsentCategory $default): string
    {
        return (string) preg_replace_callback(
            '/<script\b([^>]*)>(.*?)<\/script>/is',
            function (array $match) use ($default): string {
                [$tag, $attributes, $code] = $match;

                if ($this->isManaged($attributes)) {
                    return $tag;
                }

                if ($this->isJsonOrTemplate($attributes)) {
                    return $tag;
                }

                $src = $this->attribute($attributes, 'src');
                $category = $this->classify($src ?? $code) ?? $default;

                if ($category === ConsentCategory::Necessary) {
                    return $tag;
                }

                $rewritten = $this->removeAttribute($attributes, 'type');

                if ($src !== null) {
                    $rewritten = $this->removeAttribute($rewritten, 'src');
                    $rewritten .= ' data-src="'.$this->escape($src).'"';
                }

                return "<script type=\"text/plain\" data-cookieconsent=\"{$category->value}\"{$this->pad($rewritten)}>{$code}</script>";
            },
            $html,
        );
    }

    protected function blockIframes(string $html, ConsentCategory $default): string
    {
        return (string) preg_replace_callback(
            '/<iframe\b([^>]*)>/i',
            function (array $match) use ($default): string {
                [$tag, $attributes] = $match;

                if ($this->isManaged($attributes)) {
                    return $tag;
                }

                $src = $this->attribute($attributes, 'src');

                if ($src === null) {
                    return $tag;
                }

                $category = $this->classify($src) ?? $default;

                if ($category === ConsentCategory::Necessary) {
                    return $tag;
                }

                $rewritten = $this->removeAttribute($attributes, 'src');

                return "<iframe data-cookieconsent=\"{$category->value}\" data-cc-src=\"{$this->escape($src)}\"{$this->pad($rewritten)}>";
            },
            $html,
        );
    }

    protected function blockImages(string $html): string
    {
        return (string) preg_replace_callback(
            '/<img\b([^>]*)>/i',
            function (array $match): string {
                [$tag, $attributes] = $match;

                if ($this->isManaged($attributes)) {
                    return $tag;
                }

                $src = $this->attribute($attributes, 'src');

                if ($src === null) {
                    return $tag;
                }

                $category = $this->classify($src);

                if ($category === null) {
                    return $tag;
                }

                if ($category === ConsentCategory::Necessary) {
                    return $tag;
                }

                $rewritten = $this->removeAttribute($attributes, 'src');

                return "<img data-cookieconsent=\"{$category->value}\" data-cc-src=\"{$this->escape($src)}\"{$this->pad($rewritten)}>";
            },
            $html,
        );
    }

    protected function isManaged(string $attributes): bool
    {
        return (bool) preg_match('/\bdata-(cookieconsent|cc-src)\b/i', $attributes);
    }

    protected function isJsonOrTemplate(string $attributes): bool
    {
        $type = $this->attribute($attributes, 'type');

        if ($type === null) {
            return false;
        }

        return ! in_array(strtolower($type), ['text/javascript', 'application/javascript', 'module'], true);
    }

    protected function attribute(string $attributes, string $name): ?string
    {
        if (! preg_match('/\b'.preg_quote($name, '/').'\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $attributes, $match)) {
            return null;
        }

        return html_entity_decode($match[2] !== '' ? $match[2] : ($match[3] !== '' ? $match[3] : ($match[4] ?? '')));
    }

    protected function removeAttribute(string $attributes, string $name): string
    {
        return trim((string) preg_replace('/\s*\b'.preg_quote($name, '/').'\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $attributes));
    }

    protected function pad(string $attributes): string
    {
        $attributes = trim($attributes);

        return $attributes === '' ? '' : " {$attributes}";
    }

    protected function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', false);
    }
}
