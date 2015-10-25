<?php

namespace idfly\sitemap\commands;

class SitemapController extends \yii\console\Controller
{
    public function actionWrite()
    {
        $this->module->write();
    }
}
