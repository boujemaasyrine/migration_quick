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

class CastDateToString extends FunctionNode
{
    public $date;

    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return "to_char(".$sqlWalker->walkArithmeticPrimary($this->date).", 'DD/MM/YYYY')";
    }

    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->date = $parser->ArithmeticPrimary();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
