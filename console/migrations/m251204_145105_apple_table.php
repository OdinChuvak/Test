<?php

use yii\db\Migration;

class m251204_145105_apple_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Создаем таблицу яблок
        $this->createTable('{{%apple}}', [
            'id' => $this->primaryKey()->comment('ID яблока'),
            'color' => $this->string(50)->notNull()->comment('Цвет яблока'),
            'appearance_date' => $this->integer()->notNull()->comment('Дата появления (timestamp)'),
            'fall_date' => $this->integer()->null()->comment('Дата падения (timestamp)'),
            'status' => $this->tinyInteger()->notNull()->defaultValue(1)->comment('Статус: 1-на дереве, 2-упало, 3-сгнило'),
            'eaten_percent' => $this->decimal(5, 2)->notNull()->defaultValue(0)->comment('Процент съеденного (0-100)'),
            'rotten_date' => $this->integer()->null()->comment('Дата когда стало гнилым (timestamp)'),
            'created_at' => $this->integer()->notNull()->comment('Дата создания записи'),
            'updated_at' => $this->integer()->notNull()->comment('Дата обновления записи'),
        ]);

        // Добавляем индексы для оптимизации запросов
        $this->createIndex('idx-apple-status', '{{%apple}}', 'status');
        $this->createIndex('idx-apple-color', '{{%apple}}', 'color');
        $this->createIndex('idx-apple-appearance_date', '{{%apple}}', 'appearance_date');
        $this->createIndex('idx-apple-fall_date', '{{%apple}}', 'fall_date');

        // Добавляем комментарий к таблице
        $this->addCommentOnTable('{{%apple}}', 'Таблица для хранения яблок');

        // Вставляем тестовые данные
        $this->insertTestData();
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%apple}}');
    }

    /**
     * Вставка тестовых данных
     */
    private function insertTestData()
    {
        $colors = ['#FF0000', '#00FF00', '#0000FF', '#FFFF00', '#FFA500', '#800080'];
        $time = time();

        for ($i = 0; $i < 10; $i++) {
            $appearanceDate = $time - rand(3600, 2592000); // От 1 часа до 30 дней назад

            // Случайный статус
            $status = rand(1, 3);
            $fallDate = null;
            $rottenDate = null;
            $eatenPercent = 0;

            if ($status >= 2) { // Если упало или сгнило
                $fallDate = $appearanceDate + rand(3600, 86400); // Упало через 1-24 часа

                if ($status == 3) { // Если сгнило
                    $rottenDate = $fallDate + rand(10800, 172800); // Сгнило через 3-48 часов после падения
                    $eatenPercent = rand(0, 50); // Частично могли съесть до гниения
                } else {
                    $eatenPercent = rand(0, 100); // Упавшее яблоко могло быть частично съедено
                }
            }

            $this->insert('{{%apple}}', [
                'color' => $colors[array_rand($colors)],
                'appearance_date' => $appearanceDate,
                'fall_date' => $fallDate,
                'status' => $status,
                'eaten_percent' => $eatenPercent,
                'rotten_date' => $rottenDate,
                'created_at' => $time,
                'updated_at' => $time,
            ]);
        }

        echo "Добавлено 10 тестовых яблок\n";
    }
}
