<?php 

class ChangePasswordForm extends CFormModel
{
    public $original_password;
    public $old_password;
    public $new_password;



    public function rules()
    {
        return array(
            array('old_password,new_password', 'filter', 'filter' => 'trim'),
            array('old_password,new_password', 'required'),
            array('old_password', 'length', 'min' => Users::PASSWORD_MIN_LENGTH),
            array('new_password', 'length', 'min' => Users::PASSWORD_MIN_LENGTH),
        );
    }

    public function attributeLabels()
    {
        return array(
            'old_password' => Yii::t('main', 'Старый пароль'),
            'new_password' => Yii::t('main', 'Новый пароль'),
        );
    }

    public function changePassword()
    {
        try
        {
            $l2 = l2('ls', user()->getLsId())->connect();

            $newPassword = $l2->passwordEncrypt($this->new_password);
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
                        'password' => $this->new_password,
                    ));
                }

                // Логирую действие юзера
                if(app()->params['user_actions_log'])
                {
                    $log = new UserActionsLog();

                    $log->user_id = user()->getId();
                    $log->action_id = UserActionsLog::ACTION_CHANGE_PASSWORD;

                    $log->save(FALSE);
                }

                return TRUE;
            }
        }
        catch(Exception $e)
        {
            Yii::log("Не удалось сменить пароль от аккаунта\nOld password: " . $this->old_password . "\nNew password: " . $this->new_password . "\nError: " .  $e->getMessage() . "\n", CLogger::LEVEL_ERROR, 'cabinet_change_password');
        }

        return FALSE;
    }
}
 