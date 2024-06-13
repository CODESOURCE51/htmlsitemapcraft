<?php

namespace rp\htmlsitemap\migrations;

use craft\db\Migration;

/**
 * Install migration.
 */
class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable('{{%sitemaps}}', [
            'id' => $this->primaryKey(),
            'element_id' => $this->integer(11)->notNull(),
            'element_type' => $this->string(255),
            'ignore' => $this->boolean()->defaultValue(false),
            'url' => $this->string(255),
            'label' => $this->string(255),
            'parent' => $this->integer(11),
            'order' => $this->integer(11),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->addForeignKey('sitemaps_parent', '{{%sitemaps}}', ['parent'], '{{%sitemaps}}', ['id'], 'SET NULL', null);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropTableIfExists('{{%sitemaps}}');
    }
}
