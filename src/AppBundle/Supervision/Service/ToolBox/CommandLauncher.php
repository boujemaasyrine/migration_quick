<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 18/02/2016
 * Time: 15:10
 */

namespace AppBundle\Supervision\Service\ToolBox;

use Symfony\Component\HttpKernel\Kernel;

class CommandLauncher
{

    private $phpCliInterpreter;
    private $sfConsole;
    private $asynchPostFix;
    private $env;

    public function __construct($phpCli, $appDir, $asynchPostFix, Kernel $kernel)
    {

        $this->phpCliInterpreter = $phpCli;
        $this->sfConsole = $appDir."/console ";
        $this->asynchPostFix = $asynchPostFix;
        $this->env = $kernel->getEnvironment();
    }

    public function execute($command, $sfCommand = true, $phpCommand = true, $async = true)
    {

        if ($sfCommand === true) {
            $cmd = $this->phpCliInterpreter." ".$this->sfConsole." ".$command." --env=".$this->env;
        } elseif ($phpCommand === true) {
            $cmd = $this->phpCliInterpreter."  ".$command." ";
        } else {
            $cmd = $command;
        }

        if ($async === true) {
            $cmd = $cmd." ".$this->asynchPostFix;
        }

        //echo "Executing $cmd \n ";die;
        return `$cmd`;
    }
}
