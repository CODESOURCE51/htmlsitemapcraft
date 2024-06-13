<?php

namespace rp\htmlsitemap\controllers;

use Craft;
use rp\htmlsitemap\HtmlSitemap;
use rp\htmlsitemap\records\Sitemap as SitemapRecord;
use rp\htmlsitemap\assets\SitemapAssets;
use craft\web\Controller;

class HtmlsitemapController extends Controller
{
    public function actionIndex()
    {
        $this->view->registerAssetBundle(SitemapAssets::class);
        $this->renderTemplate('htmlsitemap/sitemaps', [
            'sitemaps' => HtmlSitemap::$plugin->sitemap->getTree(true)
        ]);
    }

    public function actionUtilities()
    {
        $this->renderTemplate('htmlsitemap/utilities');
    }

    public function actionSave()
    {
        $this->requirePostRequest();
        $data = $this->request->getRequiredBodyParam('sitemaps');
        $data = json_decode($data, true);
        $items = $data['items'];
        $toDelete = $data['delete'];
        HtmlSitemap::$plugin->sitemap->deleteSitemapsById($toDelete);
        if (HtmlSitemap::$plugin->sitemap->save($items)) {
            \Craft::$app->session->setNotice('Sitemap has been saved');
        } else {
            \Craft::$app->session->setError('Error while saving sitemap');
        }
        return $this->redirect('sitemaps');
    }

    public function actionReinstall()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        HtmlSitemap::$plugin->sitemap->reinstall();
        return $this->asJson([
            'message' => \Craft::t('htmlsitemap', 'Sitemaps have been reinstalled')
        ]);
    }

    public function actionCreateCustom()
    {
        $url = $this->request->getRequiredParam('url');
        $label = $this->request->getRequiredParam('label');
        $lastElem = SitemapRecord::find()->orderBy('order desc')->where('parent is null')->one();
        if (!$lastElem) {
            $order = 0;
        } else {
            $order = $lastElem->order + 1;
        }
        $sitemap = new SitemapRecord([
            'element_type' => 'custom',
            'ignore' => 0,
            'url' => $url,
            'label' => $label,
            'order' => $order
        ]);
        $sitemap->save(false);
        return $this->asJson($sitemap->toArray());
    }
}
