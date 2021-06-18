<?php

declare(strict_types=1);

namespace Bolt\Doctrine\Query;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

class Cast extends FunctionNode
{
    /** @var Node|string */
    protected $first;

    /** @var string */
    protected $second;

    public function getSql(SqlWalker $sqlWalker): string
    {
        $backend_driver = $sqlWalker->getConnection()->getDatabasePlatform()->getName();

        // test if we are using MySQL
        if (mb_strpos($backend_driver, 'mysql') !== false) {
            // YES we are using MySQL
            // how do we know what type $this->first is? For now hardcoding
            // type(t.value) = JSON for MySQL. JSONB for others.
            // alternatively, test if true: $this->first->dispatch($sqlWalker)==='b2_.value',
            // b4_.value for /bolt/new/showcases
            if ($this->first->dispatch($sqlWalker) === 'b2_.value' ||
                 $this->first->dispatch($sqlWalker) === 'b4_.value') {
                return $this->first->dispatch($sqlWalker);
            }
        }

        if (! mb_strpos($backend_driver, 'sqlite') && $this->second === 'TEXT') {
            $this->second = 'CHAR';
        }

        return sprintf('CAST(%s AS %s)',
            $this->first->dispatch($sqlWalker),
            $this->second
            );
    }

    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->first = null;// THIS NEEDS TO BE OUR CUSTOM COALESCE. HOW?!
        $parser->match(Lexer::T_AS);
        $parser->match(Lexer::T_IDENTIFIER);
        $this->second = $parser->getLexer()->token['value'];
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
