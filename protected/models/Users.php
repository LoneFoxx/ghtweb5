<?php

/**
 * This is the model class for table "{{users}}".
 *
 * The followings are the available columns in table '{{users}}':
 * @property string $user_id
 * @property string $login
 * @property string $password
 * @property string $email
 * @property int $activated
 * @property string $activated_hash
 * @property string $referer
 * @property string $role
 * @property string $auth_hash
 * @property string $registration_ip
 * @property integer $ls_id
 * @property string $updated_at
 * @property string $created_at
 *
 * The followings are the available model relations:
 * @property UserProfiles $profile
 * @property Transactions $transactions
 * @property Referals $referals
 * @property UserBonuses $bonuses
 * @property Ls $ls
 */
class Users extends ActiveRecord
{
    //const COUNT_FAILED_LOGIN_ATTEMPTS = 3; // Кол-во попыток перед показом капчи
    //const COUNT_FAILED_LOGIN_ATTEMPTS_FOR_BLOCKED_FORM = 10; // Кол-во попыток перед блокировкой формы


    // Login
    const LOGIN_MIN_LENGTH = 6;
    const LOGIN_MAX_LENGTH = 14;
    const LOGIN_REGEXP     = 'A-Za-z0-9-';

    // Password
    const PASSWORD_MIN_LENGTH = 6;
    const PASSWORD_MAX_LENGTH = 16;

    // Referer
    const REFERER_MIN_LENGTH = 6;
    const REFERER_MAX_LENGTH = 10;

    // Roles
    const ROLE_DEFAULT = 'user';
    const ROLE_BANNED  = 'banned';
    const ROLE_ADMIN   = 'admin';

    // Active status
    const STATUS_INACTIVATED = 0;
    const STATUS_ACTIVATED   = 1;



    public function primaryKey()
    {
        return 'user_id';
    }

    /**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{users}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		return array(
            array('user_id, login, password, email, activated, referer, role, auth_hash, registration_ip, ls_id', 'filter', 'filter' => 'trim'),

            array('user_id, login, email', 'safe', 'on' => 'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		return array(
            'profile' => array(self::HAS_ONE, 'UserProfiles', 'user_id'),
            'transactions' => array(self::HAS_MANY, 'Transactions', 'user_id', 'order' => 'created_at DESC'),
            'referals' => array(self::HAS_MANY, 'Referals', array('referer' => 'user_id')),
            'bonuses' => array(self::HAS_MANY, 'UserBonuses', 'user_id'),
            'ls' => array(self::HAS_ONE, 'Ls', array('id' => 'ls_id')),
		);
	}

    public function scopes()
    {
        return array(
            'activated' => array(
                'condition' => 'activated = :activated',
                'params' => array(':activated' => self::STATUS_ACTIVATED),
            ),
        );
    }

    protected function beforeSave()
    {
        if($this->isNewRecord)
        {
            $this->referer         = self::generateRefererCode();
            $this->registration_ip = userIp();
        }

        return parent::beforeSave();
    }

    protected function afterSave()
    {
        if($this->isNewRecord)
        {
            $model = new UserProfiles();
            $model->balance = UserProfiles::DEFAULT_BALANCE;
            $model->user_id = $this->getPrimaryKey();

            $model->save(FALSE);
        }

        parent::afterSave();
    }

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'login'             => Yii::t('main', 'Логин'),
			'password'          => Yii::t('main', 'Пароль'),
			'email'             => Yii::t('main', 'Email'),
			'activated'         => Yii::t('main', 'Статус'),
			'activated_hash'    => Yii::t('main', 'Активационный хэш'),
			'referer'           => Yii::t('main', 'Реферальный код'),
			'role'              => Yii::t('main', 'Роль'),
			'auth_hash'         => Yii::t('main', 'Хэш'),
			'registration_ip'   => Yii::t('main', 'Регистрационный IP'),
			'ls_id'             => Yii::t('main', 'Сервер'),
			'verifyCode'        => Yii::t('main', 'Код с картинки'),
			'old_password'      => Yii::t('main', 'Старый пароль'),
			'new_password'      => Yii::t('main', 'Новый пароль'),
            'created_at'        => Yii::t('main', 'Дата создания'),
            'updated_at'        => Yii::t('main', 'Дата обновления'),
		);
	}

	public function search()
	{
		$criteria = new CDbCriteria;

		$criteria->compare($this->getTableAlias() . '.user_id', $this->user_id, TRUE);
		$criteria->compare($this->getTableAlias() . '.login', $this->login, TRUE);
		$criteria->compare($this->getTableAlias() . '.email', $this->email, TRUE);
		$criteria->compare($this->getTableAlias() . '.ls_id', $this->ls_id, TRUE);

        $criteria->with = array('profile', 'ls', 'referals');
        $criteria->order = 't.created_at DESC';

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
            'pagination' => array(
                'pageSize' => 15,
                'pageVar' => 'page',
            ),
		));
	}

    /**
     * Проверяет пароли на совпадение
     *
     * @param string $new_password
     * @param string $old_password
     *
     * @return bool
     */
    public static function validatePassword($new_password, $old_password)
    {
        return CPasswordHelper::verifyPassword($new_password, $old_password);
    }

    /**
     * Хэширует пароль
     *
     * @param string $password
     *
     * @return string
     */
    public static function hashPassword($password)
    {
        return CPasswordHelper::hashPassword($password);
    }

    /**
     * Генерация нового пароля
     *
     * @param int $length
     *
     * @return string
     */
    public static function generatePassword($length = 10)
    {
        return randomString($length);
    }

    /**
     * Генерация реферального кода
     *
     * @return string
     */
    public static function generateRefererCode()
    {
        while(TRUE)
        {
            $code = strtolower(randomString(rand(self::REFERER_MIN_LENGTH, self::REFERER_MAX_LENGTH)));

            $res = db()->createCommand("SELECT COUNT(0) FROM {{users}} WHERE referer = :referer LIMIT 1")
                ->queryScalar(array(
                    'referer' => $code,
                ));

            if(!$res)
            {
                break;
            }
        }

        return $code;
    }

    /**
     * Генерация кода для активации Мастер аккаунта
     *
     * @return string
     */
    public static function generateActivatedHash()
    {
        return md5(uniqid() . time() . userIp());
    }

    /**
     * Генерация уникального хэша для авторизации
     *
     * @return string
     */
    public static function generateAuthHash()
    {
        return self::generateActivatedHash();
    }

    /**
     * Генерация логина
     *
     * @param int $minLength
     * @param int $maxLength
     *
     * @return string
     */
    public static function generateLogin($minLength = 6, $maxLength = 8)
    {
        return randomString(rand($minLength, $maxLength));
    }

    public function getActivatedStatusList()
    {
        return array(
            self::STATUS_ACTIVATED   => Yii::t('main', 'Активирован'),
            self::STATUS_INACTIVATED => Yii::t('main', 'Не активирован'),
        );
    }

    public function getActivatedStatus()
    {
        $data = $this->getActivatedStatusList();
        return isset($data[$this->activated]) ? $data[$this->activated] : Yii::t('backend', '*Unknown*');
    }

    /**
     * Список ролей
     *
     * @return array
     */
    public function getRoleList()
    {
        return array(
            self::ROLE_DEFAULT => Yii::t('main', 'Юзер'),
            self::ROLE_ADMIN   => Yii::t('main', 'Админ'),
            self::ROLE_BANNED  => Yii::t('main', 'Забанен'),
        );
    }

    /**
     * Текущая роль
     *
     * @return string
     */
    public function getRole()
    {
        $data = $this->getRoleList();
        return isset($data[$this->role]) ? $data[$this->role] : Yii::t('main', '*Unknown*');
    }

    /**
     * Возвращает логин
     *
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * Возвращает реферера
     *
     * @return string
     */
    public function getReferer()
    {
        return $this->referer;
    }

    /**
     * IP с которого регистрировался юзер
     *
     * @return string
     */
    public function getRegistrationIp()
    {
        return $this->registration_ip;
    }

    /**
     * @return int
     */
    public function getLsId()
    {
        return $this->ls_id;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getActivatedHash()
    {
        return $this->activated_hash;
    }

    /**
     * @return string
     */
    public function getAuthHash()
    {
        return $this->auth_hash;
    }

    /**
     * @return UserProfiles
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * @return Transactions
     */
    public function getTransactions()
    {
        return $this->transactions;
    }

    /**
     * @return Referals
     */
    public function getReferals()
    {
        return $this->referals;
    }

    /**
     * @return UserBonuses
     */
    public function getBonuses()
    {
        return $this->bonuses;
    }

    /**
     * @return Ls
     */
    public function getLs()
    {
        return $this->ls;
    }

    /**
     * @return bool
     */
    public function isAdmin()
    {
        return $this->role == self::ROLE_ADMIN;
    }

    /**
     * @return bool
     */
    public function isBanned()
    {
        return $this->role == self::ROLE_BANNED;
    }

    /**
     * @return bool
     */
    public function isActivated()
    {
        return $this->activated == self::STATUS_ACTIVATED;
    }
}
