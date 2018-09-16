<?php

declare(strict_types=1);

namespace Bolt\Utils;

/**
 * This class is a light interface between an external Markdown parser library
 * and the application. It's generally recommended to create these light interfaces
 * to decouple your application from the implementation details of the third-party library.
 *
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class Markdown
{
    private $parser;
    private $purifier;

    public function __construct()
    {
        $this->parser = new \Parsedown();

        $purifierConfig = \HTMLPurifier_Config::create([
            'Cache.DefinitionImpl' => null, // Disable caching
        ]);
        $this->purifier = new \HTMLPurifier($purifierConfig);
    }

    public function toHtml(string $text): string
    {
        $html = $this->parser->text($text);
        $safeHtml = $this->purifier->purify($html);

        return $safeHtml;
    }
}
