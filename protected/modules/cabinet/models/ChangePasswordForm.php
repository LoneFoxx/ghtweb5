<?php 

class ChangePasswordForm extends CFormModel
{
    /**
     * Оригинальный пароль
     * @var string
     */
    public $original_password;

    /**
     * Старый пароль
     * @var string
     */
    public $old_password;

    /**
     * Новый пароль
     * @var string
     */
    public $new_password;

    /**
     * @var Lineage
     */
    private $_l2;



    public function rules()
    {
        return array(
            array('old_password,new_password', 'filter', 'filter' => 'trim'),
            array('old_password,new_password', 'required'),
            array('old_password', 'length', 'min' => Users::PASSWORD_MIN_LENGTH),
            array('new_password', 'length', 'min' => Users::PASSWORD_MIN_LENGTH),
            array('old_password', 'checkOldPassword'),
        );
    }

    public function attributeLabels()
    {
        return array(
            'old_password' => Yii::t('main', 'Старый пароль'),
            'new_password' => Yii::t('main', 'Новый пароль'),
        );
    }

    /**
     * Проверка старого пароля
     *
     * @param string $attr
     */
    public function checkOldPassword($attr)
    {
        if(!$this->hasErrors())
        {
            try
            {
                $l2      = $this->getL2();
                $login   = user()->get('login');
                $account = $l2->getDb()->createCommand("SELECT password FROM {{accounts}} WHERE login = :login LIMIT 1")
                    ->queryRow(TRUE, array(
                        'login' => $login,
                    ));

                if(!isset($account['password']))
                {
                    $this->addError($attr, Yii::t('main', 'Аккаунт не найден.'));
                }
                elseif($account['password'] != $l2->passwordEncrypt($this->getOldPassword()))
                {
                    $this->addError($attr, Yii::t('main', 'Старый пароль и текущий пароли не совпадают.'));
                }
            }
            catch(Exception $e)
            {
                $this->addError($attr, Yii::t('main', 'Произошла ошибка! Попробуйте повторить позже.'));
            }
        }
    }

    /**
     * @return Lineage
     */
    public function getL2()
    {
        if(!$this->_l2)
        {
            $this->_l2 = l2('ls', user()->getLsId())->connect();
        }

        return $this->_l2;
    }

    public function changePassword()
    {
        try
        {
            $l2 = $this->getL2();

            $newPassword = $l2->passwordEncrypt($this->getNewPassword());
            $login       = user()->get('login');

            $res = $l2->getDb()->createCommand("UPDATE {{accounts}} SET password = :password WHERE login = :login LIMIT 1")
                ->bindParam('password', $newPassword, PDO::PARAM_STR)
                ->bindParam('login', $login, PDO::PARAM_STR)
                ->execute();

            if($res !== FALSE)
            {
                if(user()->get('email'))
                {
                    notify()->changePassword(user()->get('email'), array(
                        'password' => $this->getNewPassword(),
                    ));
                }

                // Логирую действие юзера
                if(app()->params['user_actions_log'])
                {
                    $log = new UserActionsLog();

                    $log->user_id   = user()->getId();
                    $log->action_id = UserActionsLog::ACTION_CHANGE_PASSWORD;

                    $log->save(FALSE);
                }

                return TRUE;
            }
        }
        catch(Exception $e)
        {
            Yii::log("Не удалось сменить пароль от аккаунта\nOld password: " . $this->getOldPassword() . "\nNew password: " . $this->getNewPassword() . "\nError: " .  $e->getMessage() . "\n", CLogger::LEVEL_ERROR, 'cabinet_change_password');
        }

        return FALSE;
    }

    /**
     * @return string
     */
    public function getOldPassword()
    {
        return $this->old_password;
    }

    /**
     * @return string
     */
    public function getNewPassword()
    {
        return $this->new_password;
    }
}
 