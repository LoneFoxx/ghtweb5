<?php

class AdminIdentity extends CUserIdentity
{
    private $_id;

    const ERROR_STATUS_INACTIVE     = 3;
    const ERROR_STATUS_BANNED       = 4;
    const ERROR_STATUS_IP_NO_ACCESS = 5;

    private $_user;



    public function __construct($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

	public function authenticate()
	{
        $userIp      = userIp();
        $this->_user = Users::model()->with('profile')->find('login = :login AND role = :role', array(
            'login' => $this->username,
            'role'  => Users::ROLE_ADMIN,
        ));

        if($this->_user === NULL)
        {
            $this->errorCode = self::ERROR_USERNAME_INVALID;
        }
        elseif(Users::validatePassword($this->password, $this->_user->password) === FALSE)
        {
            $this->errorCode = self::ERROR_PASSWORD_INVALID;

            // Сохраняю неудачную попытку входа
            UsersAuthLogs::model()->addErrorAuth($this->_user->getPrimaryKey());
        }
        elseif($this->_user->activated == Users::STATUS_INACTIVATED)
        {
            $this->errorCode = self::ERROR_STATUS_INACTIVE;
        }
        elseif($this->_user->role == Users::ROLE_BANNED)
        {
            $this->errorCode = self::ERROR_STATUS_BANNED;
        }
        elseif($this->_user->profile->protected_ip && !in_array($userIp, $this->_user->profile->protected_ip))
        {
            $this->errorCode = self::ERROR_STATUS_IP_NO_ACCESS;
        }
        else
        {
            $this->_id = $this->_user->getPrimaryKey();

            $this->_user->auth_hash = Users::generateAuthHash();

            $this->setState('auth_hash', $this->_user->auth_hash);

            $this->_user->save(FALSE, array('auth_hash', 'updated_at'));

            // Запись в лог
            UsersAuthLogs::model()->addSuccessAuth($this->_user->getPrimaryKey());

            $this->errorCode = self::ERROR_NONE;
        }

        return !$this->errorCode;
	}

    public function getName()
    {
        return $this->username;
    }
}
