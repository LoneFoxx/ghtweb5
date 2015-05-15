<?php

/**
 * Модель формы авторизации
 *
 * Class LoginForm
 */
class LoginForm extends CFormModel
{
    /**
     * Логин
     * @var string
     */
    public $login;

    /**
     * Пароль
     * @var string
     */
    public $password;

    /**
     * Список серверов
     * @var Gs[]
     */
    public $gs_list;

    /**
     * Выбранный сервер
     * @var int
     */
    public $gs_id;

    /**
     * ID логина от выбранного сервера
     * @var
     */
    public $ls_id;

    /**
     * Код с картинки
     * @var string
     */
    public $verifyCode;



    public function rules()
    {
        $rules = array(
            array('gs_id,login,password', 'filter', 'filter' => 'trim'),
            array('gs_id,login,password', 'required'),
            array('login', 'length', 'min' => Users::LOGIN_MIN_LENGTH, 'max' => Users::LOGIN_MAX_LENGTH),
            array('password', 'length', 'min' => Users::PASSWORD_MIN_LENGTH, 'max' => Users::PASSWORD_MAX_LENGTH),
            array('login', 'loginExists'),
            array('gs_id', 'gsIsExists'),
        );

        // Captcha
        $captcha = config('login.captcha.allow') && CCaptcha::checkRequirements();

        if($captcha)
        {
            $rules[] = array('verifyCode', 'filter', 'filter' => 'trim');
            $rules[] = array('verifyCode', 'required');
            $rules[] = array('verifyCode', 'validators.CaptchaValidator');
        }

        return $rules;
    }

    protected function afterConstruct()
    {
        $this->gs_list = Gs::model()->getOpenServers();

        if(count($this->gs_list) == 1)
        {
            $this->gs_id = key($this->gs_list);
        }

        parent::afterConstruct();
    }

    protected function afterValidate()
    {
        $this->ls_id = $this->gs_list[$this->gs_id]['login_id'];

        parent::afterValidate();
    }

    /**
     * Проверка логина на сервере
     *
     * @param $attr
     */
    public function loginExists($attr)
    {
        if(!$this->hasErrors($attr))
        {
            $siteAccountUserId = NULL;

            try
            {
                $found = FALSE;
                $login = $this->getLogin();
                $lsId  = $this->getLsId();

                $l2 = l2('ls', $lsId)->connect();

                $command = $l2->getDb()->createCommand();

                $command->where('login = :login', array(
                    'login' => $login,
                ));

                $command->from('accounts');

                $account = $command->queryRow();

                // Ищю аккаунт на сайте
                $siteAccount = db()->createCommand("SELECT user_id FROM {{users}} WHERE login = :login LIMIT 1")
                    ->queryRow(TRUE, array(
                        'login' => $login,
                    ));

                if(isset($siteAccount['user_id']))
                {
                    $siteAccountUserId = $siteAccount['user_id'];
                }

                // Аккаунт на сервере найден
                if($account)
                {
                    if($account['password'] == $l2->passwordEncrypt($this->getPassword()))
                    {
                        // Аккаунта на сайте нет, создаю его так как на сервере он уже есть
                        if(!$siteAccount)
                        {
                            $email = NULL;

                            $columnNames = $l2->getDb()
                                ->getSchema()
                                ->getTable('accounts')
                                ->getColumnNames();

                            if(is_array($columnNames))
                            {
                                foreach($columnNames as $column)
                                {
                                    if(strpos($column, 'mail') !== FALSE && isset($account[$column]))
                                    {
                                        $email = $account[$column];
                                    }
                                }
                            }

                            // Создаю аккаунт на сайте
                            $userModel = new Users();

                            $userModel->password  = NULL;
                            $userModel->login     = $login;
                            $userModel->email     = $email;
                            $userModel->activated = Users::STATUS_ACTIVATED;
                            $userModel->role      = Users::ROLE_DEFAULT;
                            $userModel->ls_id     = $lsId;

                            $userModel->save(FALSE);

                            $siteAccountUserId = $userModel->getPrimaryKey();
                        }

                        $found = TRUE;
                    }
                }

                // Аккаунт не найден
                if(!$found)
                {
                    if($siteAccountUserId)
                    {
                        UsersAuthLogs::model()->addErrorAuth($siteAccountUserId);
                    }

                    $this->incrementBadAttempt();
                    $this->addError($attr, Yii::t('main', 'Неправильный Логин или Пароль.'));
                }
            }
            catch(Exception $e)
            {
                $this->addError($attr, Yii::t('main', 'Произошла ошибка! Поробуйте повторить позже.'));
            }
        }
    }

    /**
     * Проверка сервера
     *
     * @param string $attribute
     */
    public function gsIsExists($attribute)
    {
        if(!isset($this->gs_list[$this->gs_id]))
        {
            $this->addError($attribute, Yii::t('main', 'Выберите сервер.'));
        }
    }

    public function attributeLabels()
    {
        return array(
            'gs_id'      => Yii::t('main', 'Сервер'),
            'login'      => Users::model()->getAttributeLabel('login'),
            'password'   => Users::model()->getAttributeLabel('password'),
            'verifyCode' => Yii::t('main', 'Код с картинки'),
        );
    }

    public function login()
    {
        $identity = new UserIdentity($this->login, $this->ls_id, $this->gs_id);
        $identity->authenticate();

        switch($identity->errorCode)
        {
            case UserIdentity::ERROR_USERNAME_INVALID:
            {
                $this->addError('status', Yii::t('main', 'Неправильный Логин или Пароль.'));
                break;
            }
            case UserIdentity::ERROR_STATUS_INACTIVE:
            {
                $this->addError('status', Yii::t('main', 'Аккаунт не активирован.'));
                break;
            }
            case UserIdentity::ERROR_STATUS_BANNED:
            {
                $this->addError('status', Yii::t('main', 'Аккаунт заблокирован.'));
                break;
            }
            case UserIdentity::ERROR_STATUS_IP_NO_ACCESS:
            {
                $this->addError('status', Yii::t('main', 'С Вашего IP нельзя зайти на аккаунт.'));
                break;
            }
            case UserIdentity::ERROR_NONE:
            {
                $identity->setState('gs_id', $this->gs_id);

                $this->clearBadAttempt();

                $duration = 3600 * 24 * 7; // 7 days
                user()->login($identity, $duration);

                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * Логин
     *
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * ID логин сервера
     *
     * @return int
     */
    public function getLsId()
    {
        return $this->gs_list[$this->gs_id]['login_id'];
    }

    /**
     * @return Gs[]
     */
    public function getGsList()
    {
        return $this->gs_list;
    }

    /**
     * @return int
     */
    public function getGsId()
    {
        return $this->gs_id;
    }

    /**
     * @return CFileCache
     */
    private function getCache()
    {
        static $cache;

        if(!$cache)
        {
            $cache = new CFileCache();
            $cache->init();
        }

        return $cache;
    }

    /**
     * @return string
     */
    private function getCacheName()
    {
        return 'count.failed.attempts' . userIp();
    }

    /**
     * Добавление неудачной попытки входа
     *
     * @return void
     */
    public function incrementBadAttempt()
    {
        $cacheName = $this->getCacheName();
        $cache     = $this->getCache();
        $count     = $this->getCountBadAttempt();

        $cache->set($cacheName, ++$count, (int) config('login.failed_attempts_blocked_time') * 60);
    }

    /**
     * @return void
     */
    public function clearBadAttempt()
    {
        $cacheName = $this->getCacheName();
        $cache     = $this->getCache();

        $cache->delete($cacheName);
    }

    /**
     * @return int
     */
    public function getCountBadAttempt()
    {
        $cacheName = $this->getCacheName();
        $cache     = $this->getCache();

        $count = $cache->get($cacheName);

        return $count === FALSE
            ? 0
            : $count;
    }

    /**
     * @return bool
     */
    public function isBlockedForm()
    {
        return (int) config('login.failed_attempts_blocked_time') > 0 && $this->getCountBadAttempt() >= (int) config('login.count_failed_attempts_for_blocked');
    }
}
