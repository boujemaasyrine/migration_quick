<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 04/05/2016
 * Time: 10:35
 */

namespace AppBundle\ToolBox\DoctrineExtension;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

class DateExtension extends FunctionNode
{

    private $dateTime;

    public function parse(Parser $parser)
    {

        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->dateTime = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $walker)
    {

        return
            $walker->walkArithmeticPrimary($this->dateTime).'::DATE ';
    }
}
