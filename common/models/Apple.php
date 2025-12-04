<?php

namespace common\models;

use Yii;
use yii\base\InvalidConfigException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Exception;

/**
 * Apple model
 *
 * @property int $id
 * @property string $color
 * @property int $appearance_date
 * @property int|null $fall_date
 * @property int $status
 * @property float $eaten_percent
 * @property int|null $rotten_date
 * @property int $created_at
 * @property int $updated_at
 */
class Apple extends ActiveRecord
{
    // Статусы яблок
    const STATUS_ON_TREE = 1;    // На дереве
    const STATUS_ON_GROUND = 2;  // Упало/лежит на земле
    const STATUS_ROTTEN = 3;     // Сгнило

    // Время в секундах до гниения упавшего яблока (5 часов)
    const ROTTEN_TIME = 18000; // 5 * 60 * 60

    // Доступные цвета яблок с названиями
    const COLORS = [
        '#FF0000' => 'Красный',
        '#00FF00' => 'Зеленый',
        '#0000FF' => 'Синий',
        '#FFFF00' => 'Желтый',
        '#FFA500' => 'Оранжевый',
        '#800080' => 'Фиолетовый',
        '#A52A2A' => 'Коричневый',
        '#FFC0CB' => 'Розовый',
        '#FFFFFF' => 'Белый',
        '#000000' => 'Черный',
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%apple}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['color', 'appearance_date'], 'required'],
            [['appearance_date', 'fall_date', 'status', 'rotten_date', 'created_at', 'updated_at'], 'integer'],
            [['eaten_percent'], 'number', 'min' => 0, 'max' => 100],
            ['eaten_percent', 'default', 'value' => 0],
            ['status', 'default', 'value' => self::STATUS_ON_TREE],
            ['status', 'in', 'range' => array_keys($this->getStatuses())],
            ['color', 'in', 'range' => array_keys(self::COLORS)],
            [['color'], 'string', 'max' => 50],

            // Валидация дат
            ['fall_date', 'validateFallDate'],
            ['rotten_date', 'validateRottenDate'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'color' => 'Цвет',
            'colorName' => 'Цвет',
            'appearance_date' => 'Дата появления',
            'appearanceDateFormatted' => 'Дата появления',
            'fall_date' => 'Дата падения',
            'fallDateFormatted' => 'Дата падения',
            'status' => 'Статус',
            'statusName' => 'Статус',
            'eaten_percent' => 'Съедено, %',
            'rotten_date' => 'Дата гниения',
            'rottenDateFormatted' => 'Дата гниения',
            'created_at' => 'Создано',
            'updated_at' => 'Обновлено',
            'size' => 'Размер яблока',
            'isRotten' => 'Гнилое',
            'timeToRot' => 'Время до гниения',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * Валидация даты падения
     */
    public function validateFallDate()
    {
        if ($this->fall_date !== null && $this->fall_date < $this->appearance_date) {
            $this->addError('fall_date', 'Дата падения не может быть раньше даты появления');
        }
    }

    /**
     * Валидация даты гниения
     */
    public function validateRottenDate()
    {
        if ($this->rotten_date !== null) {
            if ($this->fall_date === null) {
                $this->addError('rotten_date', 'Яблоко не может сгнить, пока не упало');
            } elseif ($this->rotten_date < $this->fall_date) {
                $this->addError('rotten_date', 'Дата гниения не может быть раньше даты падения');
            }
        }
    }

    /**
     * Получить список статусов
     * @return array
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_ON_TREE => 'На дереве',
            self::STATUS_ON_GROUND => 'Упало',
            self::STATUS_ROTTEN => 'Сгнило',
        ];
    }

    /**
     * Получить название статуса
     * @return string
     */
    public function getStatusName(): string
    {
        $statuses = self::getStatuses();
        return $statuses[$this->status] ?? 'Неизвестно';
    }

    /**
     * Получить название цвета
     * @return string
     */
    public function getColorName(): string
    {
        return self::COLORS[$this->color] ?? $this->color;
    }

    /**
     * Получить отформатированную дату появления
     * @return string
     * @throws InvalidConfigException
     */
    public function getAppearanceDateFormatted(): string
    {
        return $this->appearance_date ? Yii::$app->formatter->asDatetime($this->appearance_date) : '';
    }

    /**
     * Получить отформатированную дату падения
     * @return string
     * @throws InvalidConfigException
     */
    public function getFallDateFormatted(): string
    {
        return $this->fall_date ? Yii::$app->formatter->asDatetime($this->fall_date) : '';
    }

    /**
     * Получить отформатированную дату гниения
     * @return string
     * @throws InvalidConfigException
     */
    public function getRottenDateFormatted(): string
    {
        return $this->rotten_date ? Yii::$app->formatter->asDatetime($this->rotten_date) : '';
    }

    /**
     * Проверить, гнилое ли яблоко
     * @return bool
     */
    public function getIsRotten(): bool
    {
        if ($this->status === self::STATUS_ROTTEN) {
            return true;
        }

        // Проверяем, не сгнило ли упавшее яблоко по времени
        if ($this->status === self::STATUS_ON_GROUND && $this->fall_date) {
            $timeSinceFall = time() - $this->fall_date;
            return $timeSinceFall >= self::ROTTEN_TIME;
        }

        return false;
    }

    /**
     * Получить оставшийся размер яблока (в процентах)
     * @return float
     */
    public function getSize()
    {
        return 100 - $this->eaten_percent;
    }

    /**
     * Проверить, можно ли есть яблоко
     * @return bool
     */
    public function canEat(): bool
    {
        return !$this->getIsRotten() &&
            $this->status === self::STATUS_ON_GROUND &&
            $this->size > 0;
    }

    /**
     * Проверить, можно ли уронить яблоко
     * @return bool
     */
    public function canFall(): bool
    {
        return $this->status === self::STATUS_ON_TREE;
    }

    /**
     * Проверить, можно ли удалить яблоко
     * @return bool
     */
    public function canDelete(): bool
    {
        return $this->size == 0 || $this->getIsRotten();
    }

    /**
     * Уронить яблоко
     * @return bool
     * @throws Exception
     */
    public function fall(): bool
    {
        if (!$this->canFall()) {
            $this->addError('status', 'Яблоко уже упало или сгнило');
            return false;
        }

        $this->status = self::STATUS_ON_GROUND;
        $this->fall_date = time();

        return $this->save(false);
    }

    /**
     * Съесть часть яблока
     * @param float $percent Процент для съедения (1-100)
     * @return bool
     * @throws Exception|\Throwable
     */
    public function eat(float $percent): bool
    {
        if (!$this->canEat()) {
            $this->addError('status', 'Яблоко нельзя съесть');
            return false;
        }

        if (!is_numeric($percent) || $percent <= 0) {
            $this->addError('eaten_percent', 'Процент должен быть положительным числом');
            return false;
        }

        $newEatenPercent = $this->eaten_percent + $percent;

        if ($newEatenPercent > 100) {
            $this->addError('eaten_percent', 'Нельзя съесть больше 100%');
            return false;
        }

        $this->eaten_percent = $newEatenPercent;

        // Если съели полностью, удаляем яблоко
        if ($this->eaten_percent >= 100) {
            return $this->delete();
        }

        return $this->save(false);
    }

    /**
     * Обновить статус гниения
     * @return bool
     * @throws Exception
     */
    public function updateRottenStatus(): bool
    {
        if ($this->status === self::STATUS_ON_GROUND && $this->getIsRotten()) {
            $this->status = self::STATUS_ROTTEN;
            $this->rotten_date = time();
            return $this->save(false);
        }

        return true;
    }

    /**
     * Получить время до гниения в читаемом формате
     * @return string
     */
    public function getTimeToRot(): string
    {
        if ($this->status !== self::STATUS_ON_GROUND || !$this->fall_date) {
            return '';
        }

        $timeSinceFall = time() - $this->fall_date;
        $timeToRot = self::ROTTEN_TIME - $timeSinceFall;

        if ($timeToRot <= 0) {
            return 'Сгнило';
        }

        $hours = floor($timeToRot / 3600);
        $minutes = floor(($timeToRot % 3600) / 60);
        $seconds = $timeToRot % 60;

        return sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
    }

    /**
     * Получить случайный цвет
     * @return string
     */
    public static function getRandomColor(): string
    {
        $colors = array_keys(self::COLORS);
        return $colors[array_rand($colors)];
    }

    /**
     * Получить случайную дату появления (от 1 часа до 30 дней назад)
     * @return int
     */
    public static function getRandomAppearanceDate(): int
    {
        return time() - rand(3600, 2592000);
    }

    /**
     * Создать случайное яблоко
     * @return Apple
     */
    public static function createRandom(): Apple
    {
        $apple = new self();
        $apple->color = self::getRandomColor();
        $apple->appearance_date = self::getRandomAppearanceDate();
        $apple->status = self::STATUS_ON_TREE;

        return $apple;
    }

    /**
     * Создать несколько случайных яблок
     * @param int $count Количество яблок
     * @return int Количество созданных яблок
     * @throws Exception
     */
    public static function createRandomBatch($count = 5): int
    {
        $created = 0;

        for ($i = 0; $i < $count; $i++) {
            $apple = self::createRandom();
            if ($apple->save()) {
                $created++;
            }
        }

        return $created;
    }

    /**
     * Получить яблоки по статусу
     * @param int $status Статус
     * @return Apple[]|array
     */
    public static function findByStatus($status): array
    {
        return self::find()->where(['status' => $status])->all();
    }

    /**
     * Получить яблоки на дереве
     * @return Apple[]|array
     */
    public static function findOnTree(): array
    {
        return self::findByStatus(self::STATUS_ON_TREE);
    }

    /**
     * Получить упавшие яблоки
     * @return Apple[]|array
     */
    public static function findOnGround(): array
    {
        return self::findByStatus(self::STATUS_ON_GROUND);
    }

    /**
     * Получить гнилые яблоки
     * @return Apple[]|array
     */
    public static function findRotten(): array
    {
        return self::findByStatus(self::STATUS_ROTTEN);
    }

    /**
     * Удалить все гнилые яблоки
     * @return int Количество удаленных
     */
    public static function deleteAllRotten(): int
    {
        return self::deleteAll(['status' => self::STATUS_ROTTEN]);
    }

    /**
     * Проверить все упавшие яблоки на гниение
     * @return int Количество сгнивших яблок
     */
    public static function checkAllForRot(): int
    {
        $count = 0;
        $apples = self::findOnGround();

        foreach ($apples as $apple) {
            if ($apple->getIsRotten()) {
                $apple->updateRottenStatus();
                $count++;
            }
        }

        return $count;
    }

    /**
     * Получить статистику по яблокам
     * @return array
     */
    public static function getStats(): array
    {
        $stats = [];

        $stats['total'] = self::find()->count();
        $stats['on_tree'] = self::findOnTree();
        $stats['on_ground'] = self::findOnGround();
        $stats['rotten'] = self::findRotten();

        $stats['by_color'] = self::find()
            ->select(['color', 'COUNT(*) as count'])
            ->groupBy('color')
            ->indexBy('color')
            ->column();

        $stats['avg_eaten_percent'] = self::find()
            ->where(['>', 'eaten_percent', 0])
            ->average('eaten_percent');

        return $stats;
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert): bool
    {
        if (parent::beforeSave($insert)) {
            // Автоматически проверяем гниение при сохранении
            if (!$insert && $this->status === self::STATUS_ON_GROUND) {
                $this->updateRottenStatus();
            }

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function afterFind()
    {
        parent::afterFind();

        // Проверяем гниение при загрузке
        if ($this->status === self::STATUS_ON_GROUND) {
            $this->updateRottenStatus();
        }
    }
}