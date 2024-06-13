<?php

namespace Plugins\Sitemap\migrations;

use Craft;
use craft\db\Migration;

/**
 * m220324_131805_AddCustomItems migration.
 */
class m220324_131805_AddCustomItems extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('{{%sitemaps}}', 'url', $this->string(255)->after('ignore'));
        $this->addColumn('{{%sitemaps}}', 'label', $this->string(255)->after('url'));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m220324_131805_AddCustomItems cannot be reverted.\n";
        return false;
    }
}
