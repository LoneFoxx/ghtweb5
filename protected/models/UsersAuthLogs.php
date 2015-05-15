<?php

/**
 * This is the model class for table "{{users_auth_logs}}".
 *
 * The followings are the available columns in table '{{users_auth_logs}}':
 * @property string $id
 * @property string $user_id
 * @property string $ip
 * @property string $user_agent
 * @property integer $status
 * @property string $created_at
 *
 * @property Gs $gs
 */
class UsersAuthLogs extends ActiveRecord
{
    const STATUS_AUTH_SUCCESS = 1; // Авторизовался удачно
    const STATUS_AUTH_DENIED  = 0; // Авторизация не прошла



	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{users_auth_logs}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		return array(
			array('user_id, ip, created_at', 'required'),
			array('status', 'numerical', 'integerOnly'=>true),
			array('user_id', 'length', 'max'=>10),
			array('ip', 'length', 'max'=>25),
			array('user_agent', 'length', 'max'=>255),

			array('id, user_id, ip, user_agent, status, created_at', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id'         => 'ID',
			'user_id'    => Yii::t('main', 'Пользователь'),
			'ip'         => 'Ip',
			'user_agent' => Yii::t('main', 'Браузер'),
			'status'     => Yii::t('main', 'Статус'),
			'created_at' => Yii::t('main', 'Дата создания'),
		);
	}

    public function search()
    {
        $criteria = new CDbCriteria;

        $criteria->compare('id', $this->id, TRUE);
        $criteria->compare('ip', $this->ip, TRUE);
        $criteria->compare('user_agent', $this->user_agent, TRUE);
        $criteria->compare('status', $this->status);

        $criteria->order = 'created_at DESC';

        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
            'pagination' => array(
                'pageVar' => 'page',
                'pageSize' => 20,
            ),
        ));
    }

    public function getStatusList()
    {
        return array(
            self::STATUS_AUTH_SUCCESS => Yii::t('main', 'Разрешен'),
            self::STATUS_AUTH_DENIED  => Yii::t('main', 'Запрешен'),
        );
    }

    protected function beforeSave()
    {
        if($this->getIsNewRecord())
        {
            $this->ip         = userIp();
            $this->user_agent = request()->getUserAgent();
        }

        return parent::beforeSave();
    }

    /**
     * Добавление неудачной попытки авторизации
     *
     * @param $userId
     *
     * @return void
     */
    public function addErrorAuth($userId)
    {
        $model = new self();

        $model->user_id = $userId;
        $model->status  = self::STATUS_AUTH_DENIED;

        $model->save(FALSE);
    }

    /**
     * Добавление удачной попытки авторизации
     *
     * @param $userId
     *
     * @return void
     */
    public function addSuccessAuth($userId)
    {
        $model = new self();

        $model->user_id = $userId;
        $model->status  = self::STATUS_AUTH_SUCCESS;

        $model->save(FALSE);
    }
}
