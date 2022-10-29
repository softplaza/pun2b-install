<?php

// mPDF Hooks

function mpdf_es_essentials()
{
    require_once SITE_ROOT . 'apps/mpdf/vendor/composer/autoload_real.php';

    return ComposerAutoloaderInit6b009ee034741aed98420ca10ea3436c::getLoader();
}
