<?php

namespace rp\htmlsitemap\services;

use Craft;
use rp\htmlsitemap\records\Sitemap;
use rp\htmlsitemap\HtmlSitemap as SitemapPlugin;
use craft\base\Component;
use craft\base\Element;
use craft\elements\Category;
use craft\elements\Entry;
use craft\helpers\ArrayHelper;
use craft\helpers\ElementHelper;

class HtmlsitemapService extends Component
{
    protected $_elements;
    protected $_sitemaps;

    public function install()
    {
        $order = 0;
        foreach ($this->elements as $element) {
            if ($this->shouldSaveSitemap($element)) {
                $this->createSitemap($element, $order);
                $order++;
            }
        }
    }

    public function reinstall()
    {
        $ids = [];
        foreach ($this->elements as $element) {
            $sitemap = $this->handleElementSaved($element);
            if ($sitemap) {
                $ids[] = $sitemap->id;
            }
        }
        $toDelete = array_filter($this->sitemaps, function ($sitemap) use ($ids) {
            return ($sitemap->element_type != 'custom' and !in_array($sitemap->id, $ids));
        });
        foreach ($toDelete as $sitemap) {
            $this->deleteSitemap($sitemap);
        }
    }

    public function deleteSitemapByElement(Element $element)
    {
        if ($sitemap = $this->getSitemapByElement($element)) {
            $this->deleteSitemap($sitemap);
        }
    }

    public function deleteSitemapsById(array $ids)
    {
        foreach ($ids as $id) {
            $this->deleteSitemap($this->getSitemapById($id));
        }
    }

    public function deleteSitemap(Sitemap $sitemap)
    {
        $sitemap->delete();
        $sitemaps = $this->sitemaps;
        foreach ($sitemaps as $index => $sitemap2) {
            if ($sitemap->id == $sitemap2->id) {
                unset($sitemaps[$index]);
            }
        }
        $this->_sitemaps = $sitemaps;
    }

    public function save(array $tree, ?Sitemap $parent = null): bool
    {
        $order = 0;
        foreach ($tree as $elem) {
            $record = $this->getSitemapById($elem['id']);
            if ($record) {
                $record->order = $order;
                $record->ignore = $elem['ignore'];
                $record->url = $elem['url'] ?? '';
                $record->label = $elem['label'] ?? '';
                $record->parent = $parent ? $parent->id : null;
                $record->save();
                $order++;
                $this->save($elem['children'], $record);
            }
        }
        return true;
    }

    public function handleElementSaved(Element $element): ?Sitemap
    {
        $shouldSave = $this->shouldSaveSitemap($element);
        $sitemap = $this->getSitemapByElement($element);
        if ($sitemap and !$shouldSave) {
            $this->deleteSitemap($sitemap);
            return null;
        }
        if (!$sitemap and $shouldSave) {
            return $this->createSitemap($element);
        }
        return $sitemap ?? null;
    }

    public function getTree(bool $withIgnore = false)
    {
        return $this->_getTree($this->rootSitemaps, $withIgnore);
    }

    protected function getElements(): array
    {
        if ($this->_elements === null) {
            $this->_elements = Entry::find()->all();
            $this->_elements = array_merge($this->_elements, Category::find()->all());
        }
        return $this->_elements;
    }

    protected function getSitemaps(): array
    {
        if ($this->_sitemaps === null) {
            $this->_sitemaps = Sitemap::find()->orderBy('order ASC')->all();
        }
        return $this->_sitemaps;
    }

    protected function getSitemapByElement(Element $element): ?Sitemap
    {
        foreach ($this->sitemaps as $sitemap) {
            if ($sitemap->element_id == $element->id and $sitemap->element_type == get_class($element)) {
                return $sitemap;
            }
        }
        return null;
    }

    protected function getSitemapById(int $id): ?Sitemap
    {
        return ArrayHelper::firstWhere($this->sitemaps, 'id', $id);
    }

    protected function getNextRootOrder(): int
    {
        $roots = $this->getRootSitemaps();
        if (!$roots) {
            return 0;
        }
        $reverse = array_reverse($roots);
        return isset($reverse[0]) ? $reverse[0]->order + 1 : 0;
    }

    protected function createSitemap(Element $element, ?int $order = null): ?Sitemap
    {
        if (is_null($order)) {
            $order = $this->getNextRootOrder();
        }
        $record = new Sitemap([
            'element_id' => $element->id,
            'element_type' => get_class($element),
            'order' => $order
        ]);
        $record->save();
        $this->getSitemaps();
        $this->_sitemaps[] = $record;
        return $record;
    }

    protected function getRootSitemaps(): array
    {
        return ArrayHelper::where($this->sitemaps, 'parent', null);
    }

    protected function getChildrenOf(int $id): array
    {
        return ArrayHelper::where($this->sitemaps, 'parent', $id);
    }

    protected function getElement(int $id, string $class): ?Element
    {
        foreach ($this->elements as $element) {
            if (get_class($element) == $class and $element->id == $id) {
                return $element;
            }
        }
        return null;
    }

    protected function _getTree(array $sitemaps, bool $withIgnore)
    {
        $out = [];
        foreach ($sitemaps as $sitemap) {
            if ($sitemap->ignore and !$withIgnore) {
                continue;
            }
            $element = $this->getElement($sitemap->element_id, $sitemap->element_type);
            $children = $this->getChildrenOf($sitemap->id);
            $out[] = [
                'id' => $sitemap->id,
                'ignore' => $sitemap->ignore,
                'element_type' => $sitemap->element_type,
                'url' => $sitemap->url,
                'label' => $sitemap->label,
                'element' => $element,
                'type_title' => $this->getElementTypeTitle($element),
                'children' => $this->_getTree($children, $withIgnore)
            ];
        }
        return $out;
    }

    /**
     * Get the element type title, for a category for example it would be the category group name
     * 
     * @param  ?Element $element
     * @return string
     */
    protected function getElementTypeTitle(?Element $element): string
    {
        if (!$element) {
            return '';
        }
        if ($element instanceof Entry) {
            return 'Entry ' . $element->section->name;
        }
        return 'Category ' . $element->group->name ?? '';
    }

    /**
     * Should a sitemap for an element be saved
     * 
     * @param  Element $element
     * @return bool
     */
    protected function shouldSaveSitemap(Element $element): bool
    {
        if (ElementHelper::isDraftOrRevision($element) or !$element->url) {
            return false;
        }
        $settings = SitemapPlugin::$plugin->settings;
        if ($element instanceof Category and $element->status == Element::STATUS_ENABLED and !in_array($element->group->uid, $settings->allIgnored)) {
            return true;
        }
        if ($element instanceof Entry and $element->status == Entry::STATUS_LIVE and !in_array($element->section->uid, $settings->allIgnored)) {
            return true;
        }
        return false;
    }
}
