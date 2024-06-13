<?php

namespace rp\htmlsitemap\variables;

use rp\htmlsitemap\HtmlSitemap;

class HtmlSitemapVariable
{
    public function getTree()
    {
        return Sitemap::$plugin->sitemap->getTree(false);
    }

    public function render($template = 'htmlsitemap/render')
    {
        echo \Craft::$app->view->renderTemplate($template, [
            'sitemaps' => HtmlSitemap::$plugin->sitemap->getTree(false)
        ]);
    }
}
