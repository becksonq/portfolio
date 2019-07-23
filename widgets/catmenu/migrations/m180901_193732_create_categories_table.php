<?php

use yii\db\Migration;

/**
 * Handles the creation of table `categories`.
 */
class m180901_193732_create_categories_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';

        $this->createTable('{{%categories}}', [
            'id'            => $this->primaryKey(),
            'old_id'        => $this->integer()->notNull()->unique(),
            'category_name' => $this->string(100)->notNull()->unique(),
            'sort'          => $this->integer()->notNull()->unique(),
            'class_name'    => $this->string(),
            'icon'          => $this->string(),
        ], $tableOptions);

        $this->batchInsert('{{%categories}}', [
            'old_id',
            'category_name',
            'sort'
        ], [
            [1, 'Недвижимость', 1],
            [2, 'Транспорт', 2],
            [22, 'Хозяйство, быт', 3],
            [31, 'Хобби и отдых', 9],
            [38, 'Электроника', 5],
            [47, 'Услуги', 8],
            [50, 'Работа', 7],
            [51, 'Обращения', 12],
            [55, 'Строительство', 4],
            [56, 'Отдам даром', 11],
            [57, 'Всё для дачи', 10],
            [58, 'Оборудование', 6]
        ]);

        $this->addForeignKey('fk-adverts-category_id', '{{%adverts}}', 'cat_id', '{{%categories}}', 'id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-adverts-category_id', '{{%adverts}}');
        $this->dropTable('{{%categories}}');
    }
}
