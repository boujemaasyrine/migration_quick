<?php
/**
 * Created by PhpStorm.
 * User: bbarhoumi
 * Date: 08/04/2016
 * Time: 08:49
 */

namespace AppBundle\ToolBox\DoctrineExtension;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;

class CastIntegerToString extends FunctionNode
{
    public $integer;

    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return 'CAST('.$this->integer->dispatch($sqlWalker).' AS char(100))';
    }

    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->integer = $parser->SimpleArithmeticExpression();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
