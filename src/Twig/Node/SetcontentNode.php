<?php

declare(strict_types=1);

namespace Bolt\Twig\Node;

use Bolt\Twig\SetcontentExtension;
use Twig\Compiler;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Node;

/**
 * Twig setcontent node.
 *
 * @author Bob den Otter <bob@twokings.nl>
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Xiao-Hu Tai <xiao@twokings.nl>
 */
class SetcontentNode extends Node
{
    /**
     * @param int         $lineNo
     * @param string|null $tag
     */
    public function __construct($name, Node $contentType, ArrayExpression $arguments, array $whereArguments, $lineNo, $tag = null)
    {
        parent::__construct(
            $whereArguments,
            [
                'name' => $name,
                'contentType' => $contentType,
                'arguments' => $arguments,
            ],
            $lineNo,
            $tag
        );
    }

    /**
     * {@inheritdoc}
     */
    public function compile(Compiler $compiler): void
    {
        $arguments = $this->getAttribute('arguments');

        $compiler
            ->addDebugInfo($this)
            ->write("\$context['")
            ->raw($this->getAttribute('name'))
            ->raw("'] = ")
            ->raw("\$this->env->getExtension('" . SetcontentExtension::class . "')->getQueryEngine()->getContentForTwig(")
            ->subcompile($this->getAttribute('contentType'))
            ->raw(', ');

        if ($this->hasNode('wherearguments')) {
            $compiler
                ->raw('array_merge(')
                ->subcompile($arguments)
                ->raw(', ')
                ->subcompile($this->getNode('wherearguments'))
                ->raw(')');
        } else {
            $compiler
                ->subcompile($arguments);
        }

        $compiler->raw(");\n");
    }
}
