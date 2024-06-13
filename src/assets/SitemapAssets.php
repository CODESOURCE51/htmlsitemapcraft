<?php 

namespace rp\htmlsitemap\assets;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class SitemapAssets extends AssetBundle
{
    public $sourcePath = __DIR__ . '/dist';

    public $js = [
        'sortable.js'
    ];

    public $depends = [
        CpAsset::class
    ];
}