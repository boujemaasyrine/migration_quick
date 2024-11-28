<?php

namespace TranslationBundle\Finder;

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

/**
 * @author William DURAND <william.durand1@gmail.com>
 * @author Markus Poerschke <markus@eluceo.de>
 */
class TranslationFinder extends \Bazinga\Bundle\JsTranslationBundle\Finder\TranslationFinder
{
    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @param KernelInterface $kernel The kernel.
     */
    public function __construct(KernelInterface $kernel)
    {
        parent::__construct($kernel);
    }

    /**
     * Gets translation files location.
     *
     * @return array
     */
    protected function getLocations()
    {
        $locations = array();

        if (class_exists('Symfony\Component\Validator\Validation')) {
            $r = new \ReflectionClass('Symfony\Component\Validator\Validation');

            $locations[] = dirname($r->getFilename()).'/Resources/translations';
        }

        if (class_exists('Symfony\Component\Form\Form')) {
            $r = new \ReflectionClass('Symfony\Component\Form\Form');

            $locations[] = dirname($r->getFilename()).'/Resources/translations';
        }

        if (class_exists('Symfony\Component\Security\Core\Exception\AuthenticationException')) {
            $r = new \ReflectionClass('Symfony\Component\Security\Core\Exception\AuthenticationException');

            if (file_exists($dir = dirname($r->getFilename()).'/../../Resources/translations')) {
                $locations[] = $dir;
            } else {
                // Symfony 2.4 and above
                $locations[] = dirname($r->getFilename()).'/../Resources/translations';
            }
        }

        $overridePath = $this->kernel->getRootDir().'/Resources/%s/translations';
        foreach ($this->kernel->getBundles() as $bundle => $class) {
            $reflection = new \ReflectionClass($class);
            if (is_dir($dir = dirname($reflection->getFilename()).'/Resources/translations')) {
                $locations[] = $dir;
            }
            if (is_dir($dir = sprintf($overridePath, $bundle))) {
                $locations[] = $dir;
            }
        }

        if (is_dir($dir = $this->kernel->getRootDir().'/Resources/translations')) {
            $locations[] = $dir;
        }
        $config = Yaml::parse(
            file_get_contents($this->kernel->getRootDir().'/config/config.yml')
        );
        if (key_exists('framework', $config) && key_exists('translator', $config['framework']) && key_exists(
            'path',
            $config['framework']['translator']
        )) {
            $paths = $config['framework']['translator']['path'];
            foreach ($paths as $key => $path) {
                $path = str_replace('%kernel.root_dir%/..', '%s', $path);
                $paths[$key] = sprintf($path, $this->kernel->getRootDir().'/..');
            }
            $locations = array_merge($locations, $paths);
        }

        return $locations;
    }
}
