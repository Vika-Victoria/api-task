<?php

namespace app\modules\v1\models;

use Yii;

/**
 * This is the model class for table "persons".
 *
 * @property string $id
 * @property string $user
 * @property string $secret
 * @property int $created_at
 * @property int $updated_at
 */
class Persons extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'persons';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'user', 'secret'], 'required'],
            [['created_at', 'updated_at'], 'integer', 'max' => 10],
            [['id', 'user'], 'string', 'max' => 50],
            [['secret'], 'string', 'max' => 100],
            [['id'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user' => 'User',
            'secret' => 'Secret',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
