<?php

namespace App;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer as SymfonyHtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

final readonly class HtmlSanitizer
{
    private SymfonyHtmlSanitizer $sanitizer;

    public function __construct()
    {
        $config = new HtmlSanitizerConfig()
            ->allowElement("p")
            ->allowElement("br")
            ->allowElement("strong")
            ->allowElement("em")
            ->allowElement("u")
            ->allowElement("s")
            ->allowElement("ul")
            ->allowElement("ol")
            ->allowElement("li")
            ->allowElement("blockquote")
            ->allowElement("pre")
            ->allowElement("code")
            ->allowElement("a", ["href", "title"])
            ->allowLinkSchemes(["https", "http", "mailto"])
            ->forceAttribute("a", "rel", "noopener noreferrer");

        $this->sanitizer = new SymfonyHtmlSanitizer($config);
    }

    public function sanitize(string $html): string
    {
        return $this->sanitizer->sanitize($html);
    }
}
