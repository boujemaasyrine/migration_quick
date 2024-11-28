<?php

namespace TranslationBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class TranslationBundle extends Bundle
{
    public function getParent()
    {
        return 'BazingaJsTranslationBundle';
    }
}
