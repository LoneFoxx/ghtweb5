<?php

error_reporting(-1);

// Проверка установлена ли CMS
if(is_dir(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'protected' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'install') && strpos($_SERVER['REQUEST_URI'], 'install') === FALSE)
{
    header('Location: http://' . $_SERVER['HTTP_HOST'] . '/install/');
    exit;
}
// ---------------

// Путь к папке с framework
$yii = dirname(__FILE__) . '/../framework/yii.php';

if(!is_file($yii))
{
    echo 'Неправильно указан путь до папки <b>framework</b>';
    exit;
}

// Чтобы включить режим разработки добавьте свой IP в array() (тот что снизу), к примеру: array('127.0.0.1')
if(in_array($_SERVER['REMOTE_ADDR'], array()))
{
    define('YII_DEBUG', TRUE);

    $config = dirname(__FILE__) . '/protected/config/main-dev.php';
}
else
{
    error_reporting(0);

    define('YII_DEBUG', FALSE);

    $config = dirname(__FILE__) . '/protected/config/main.php';
}

// Запись в лог имени файла и номера строки
define('YII_TRACE_LEVEL', 1);

require_once dirname(__FILE__) . '/protected/helpers/global.php';

require_once $yii;

$app = Yii::createWebApplication($config);

$app->onBeginRequest = function(){

    $languages = isset(app()->params['languages']) && is_array(app()->params['languages']) && count(app()->params['languages'])
        ? app()->params['languages']
        : NULL;

    if($languages)
    {
        $cookieLanguage = !empty($_COOKIE['lang'])
            ? strtolower(trim($_COOKIE['lang']))
            : NULL;

        if(isset($languages[$cookieLanguage]))
        {
            app()->setLanguage($cookieLanguage);
        }
    }

};

$app->run();
