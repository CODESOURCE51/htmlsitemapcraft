<?php

namespace rp\htmlsitemap;

use Craft;
use rp\htmlsitemap\assets\SettingsAssets;
use rp\htmlsitemap\models\Settings;
use rp\htmlsitemap\services\HtmlsitemapService;
use rp\htmlsitemap\variables\HtmlSitemapVariable;
use craft\base\Element;
use craft\base\Plugin;
use craft\elements\Entry;
use craft\events\ModelEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\services\Elements;
use craft\web\UrlManager;
use craft\web\View;
use craft\web\twig\variables\CraftVariable;
use yii\base\Event;

class HtmlSitemap extends Plugin
{
     /**
     * @var Themes
     */
    public static $plugin;

    /**
     * @inheritdoc
     */
    public $hasCpSection = true;

    /**
     * @inheritdoc
     */
    public $hasCpSettings = true;

    public $schemaVersion = '1.2.0';

    public function init()
    {
        parent::init();

        self::$plugin = $this;

        $this->setComponents([
            'sitemap' => HtmlsitemapService::class
        ]);

        $this->registerRoutes();
        $this->registerElementsEvents();

        if (\Craft::$app->request->isSiteRequest) {
            Event::on(
                CraftVariable::class,
                CraftVariable::EVENT_INIT,
                function (Event $event) {
                    $variable = $event->sender;
                    $variable->set('sitemap', HtmlSitemapVariable::class);
                }
            );

            Event::on(View::class, View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS, function (RegisterTemplateRootsEvent $event) {
                $event->roots[''][] = __DIR__ . '/templates/front';
            });
        }
    }

    /**
     * @inheritDoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * @inheritDoc
     */
    protected function settingsHtml(): string
    {
        return \Craft::$app->view->renderTemplate(
            'htmlsitemap/settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }

    public function registerElementsEvents()
    {
        Event::on(Elements::class, Elements::EVENT_AFTER_DELETE_ELEMENT, function (Event $e) {
            HtmlSitemap::$plugin->sitemap->deleteSitemapByElement($e->element);
        });
        Event::on(Elements::class, Elements::EVENT_AFTER_RESAVE_ELEMENT, function (Event $e) {
            HtmlSitemap::$plugin->sitemap->handleElementSaved($e->element);
        });
        Event::on(Elements::class, Elements::EVENT_AFTER_SAVE_ELEMENT, function (Event $e) {
            HtmlSitemap::$plugin->sitemap->handleElementSaved($e->element);
        });
        Event::on(Elements::class, Elements::EVENT_AFTER_RESTORE_ELEMENT, function (Event $e) {
            HtmlSitemap::$plugin->sitemap->handleElementSaved($e->element);
        });
        Event::on(Elements::class, Elements::EVENT_AFTER_UPDATE_SLUG_AND_URI, function (Event $e) {
            HtmlSitemap::$plugin->sitemap->handleElementSaved($e->element);
        });
    }

    /**
     * @inheritDoc
     */
    public function getCpNavItem()
    {
        $item = parent::getCpNavItem();
        $item['url'] = 'sitemaps';
        $item['subnav'] = [
            'sitemap-sitemaps' => [
                'url' => 'sitemaps',
                'label' => \Craft::t('htmlsitemap', 'Sitemaps'),
            ],
            'sitemap-utilities' => [
                'url' => 'sitemaps/utilities',
                'label' => \Craft::t('htmlsitemap', 'Utilities'),
            ]
        ];
        return $item;
    }

    /**
     * Register routes
     */
    protected function registerRoutes()
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['sitemaps'] = 'htmlsitemap/htmlsitemap';
                $event->rules['sitemaps/utilities'] = 'htmlsitemap/htmlsitemap/utilities';
                $event->rules['sitemaps/save'] = 'htmlsitemap/htmlsitemap/save';
                $event->rules['sitemaps/reinstall'] = 'htmlsitemap/htmlsitemap/reinstall';
            }
        );
    }
}
