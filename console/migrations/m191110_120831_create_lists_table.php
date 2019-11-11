<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%lists}}`.
 */
class m191110_120831_create_lists_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%lists}}', [
            'id' => $this->primaryKey(),
            'list_id' => $this->bigInteger()->notNull(),
            'slug' => $this->string(100)->notNull(),
            'owner_screen_name' => $this->string(100)->notNull(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%lists}}');
    }
}
