<?php

namespace app\modules\v1\models;

use Yii;

/**
 * This is the model class for table "lists".
 *
 * @property int $id
 * @property int $list_id
 * @property string $slug
 * @property string $owner_screen_name
 */
class Lists extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'lists';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['list_id', 'slug', 'owner_screen_name'], 'required'],
            [['list_id'], 'integer'],
            [['slug', 'owner_screen_name'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'list_id' => 'List ID',
            'slug' => 'Slug',
            'owner_screen_name' => 'Owner Screen Name',
        ];
    }
}
