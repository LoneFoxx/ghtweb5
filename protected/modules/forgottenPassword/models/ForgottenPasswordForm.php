<?php

/**
 * Class ForgottenPasswordForm
 *
 * @property array $gs_list
 * @property int $gs_id
 * @property string $login
 * @property string $email
 * @property string $verifyCode
 */
class ForgottenPasswordForm extends CFormModel
{
    /**
     * @var Gs[]
     */
    public $gs_list;

    /**
     * @var int
     */
    public $gs_id;

    /**
     * @var string
     */
    public $login;

    /**
     * @var string
     */
    public $email;

    /**
     * @var string
     */
    public $verifyCode;



    public function rules()
    {
        $rules = array(
            array('gs_id,login,email,verifyCode', 'filter', 'filter' => 'trim'),
            array('gs_id,login,email', 'required'),
            array('login', 'length', 'min' => Users::LOGIN_MIN_LENGTH, 'max' => Users::LOGIN_MAX_LENGTH),
            array('email', 'email', 'message' => Yii::t('main', 'Введите корректный Email адрес.')),
            array('login', 'loginIsExists'),
            array('gs_id', 'gsIsExists'),
        );

        // Captcha
        $captcha = config('forgotten_password.captcha.allow') && CCaptcha::checkRequirements();

        if($captcha)
        {
            $rules[] = array('verifyCode', 'filter', 'filter' => 'trim');
            $rules[] = array('verifyCode', 'required');
            $rules[] = array('verifyCode', 'validators.CaptchaValidator');
        }

        return $rules;
    }

    public function afterConstruct()
    {
        $dependency = new CDbCacheDependency("SELECT COUNT(0), MAX(UNIX_TIMESTAMP(updated_at)) FROM {{gs}} WHERE status = :status");
        $dependency->params = array('status' => ActiveRecord::STATUS_ON);
        $dependency->reuseDependentData = TRUE;

        $res = Gs::model()->cache(3600 * 24, $dependency)->opened()->findAll();

        if($res)
        {
            foreach($res as $gs)
            {
                $this->gs_list[$gs['id']] = $gs;
            }
        }

        unset($res);

        if(count($this->gs_list) == 1)
        {
            $this->gs_id = key($this->gs_list);
        }

        parent::afterConstruct();
    }

    /**
     * Проверка сервера
     *
     * @param $attr
     * @param $params
     */
    public function gsIsExists($attr, $params)
    {
        if(!isset($this->gs_list[$this->gs_id]))
        {
            $this->addError(__FUNCTION__, Yii::t('main', 'Выберите сервер.'));
        }
    }

    /**
     * Проверка Логина и Email
     *
     * @param $attr
     * @param $params
     */
    public function loginIsExists($attr, $params)
    {
        if(!$this->hasErrors())
        {
            /** @var Users $user */
            $user = Users::model()->find('login = :login AND email = :email', array(':login' => $this->login, ':email' => $this->email));

            if($user === NULL)
            {
                $this->addError(__FUNCTION__, Yii::t('main', 'Аккаунт не найден.'));
            }
            elseif($user->isBanned())
            {
                $this->addError(__FUNCTION__, Yii::t('main', 'Аккаунт заблокирован, восстановление пароля невозможно'));
            }
            elseif(!$user->isActivated())
            {
                $this->addError(__FUNCTION__, Yii::t('main', 'Аккаунт не активирован, восстановление пароля невозможно'));
            }
            else
            {
                // Ищю аккаунт на сервере
                try
                {
                    $l2 = l2('ls', $this->gs_list[$this->gs_id]['login_id'])->connect();

                    $res = $l2->getDb()->createCommand("SELECT * FROM {{accounts}} WHERE login = :login LIMIT 1")
                        ->bindParam('login', $this->login, PDO::PARAM_STR)
                        ->queryScalar();

                    if(!$res)
                    {
                        $this->addError(__FUNCTION__, Yii::t('main', 'Аккаунт не найден.'));
                    }
                }
                catch(Exception $e)
                {
                    $this->addError(__FUNCTION__, $e->getMessage());
                }
            }
        }
    }

    public function attributeLabels()
    {
        return array(
            'gs_id'      => Yii::t('main', 'Сервер'),
            'login'      => Yii::t('main', 'Логин'),
            'email'      => Yii::t('main', 'Email'),
            'verifyCode' => Yii::t('main', 'Код с картинки'),
        );
    }
}
 