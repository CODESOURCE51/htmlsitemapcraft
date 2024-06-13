<?php 

namespace rp\htmlsitemap\records;

use craft\db\ActiveRecord;

class Sitemap extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%sitemaps%}}';
    }
}