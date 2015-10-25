<?php

namespace idfly\sitemap;

class Module {

    public $items = [];

    public $splitCount = 10000;
    public $splitSize = 1048576;

    public function write()
    {
        $path = \Yii::getAlias('@webroot');
        $url = \Yii::getAlias('@web');

        $sitemap = new Sitemap($path, $url);

        $sitemap->items = $this->items;
        $sitemap->splitCount = $this->splitCount;
        $sitemap->splitSize = $this->splitSize;
        $sitemap->write();
    }

}