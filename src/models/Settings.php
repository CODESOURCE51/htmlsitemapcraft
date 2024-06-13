<?php

namespace rp\htmlsitemap\models;

use craft\base\Model;

class Settings extends Model
{
    public $ignored = [];

    /**
     * Get all ignored uids
     * 
     * @return array
     */
    public function getAllIgnored(): array
    {
        if (!$this->ignored) {
            return [];
        }
        return $this->ignored;
    }

    /**
     * Get all defined sections, indexed by uid
     * 
     * @return array
     */
    public function getSections(): array
    {
        $sections = [];
        foreach (\Craft::$app->sections->getAllSections() as $section) {
            $sections[$section->uid] = $section->name;
        }
        return $sections;
    }

    /**
     * Get all defined category groups, indexed by uid
     * 
     * @return array
     */
    public function getCategoryGroups(): array
    {
        $cats = [];
        foreach (\Craft::$app->categories->getAllGroups() as $cat) {
            $cats[$cat->uid] = $cat->name;
        }
        return $cats;
    }
}
