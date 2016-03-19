<?php


$backendUrl = 'backend'; // Ссылка на админку (не менять!!!)



return array(
    'basePath'      => dirname(__FILE__) . DIRECTORY_SEPARATOR . '..',
    'name'          => 'GHTWEB v5',
    'sourceLanguage'=> '00',
    'language'      => 'ru',

    'aliases' => array(
        'app'        => 'application',
        'widgets'    => 'application.widgets',
        'modules'    => 'application.modules',
        'validators' => 'application.validators',
    ),

    //'basePath' => 'app',

	// preloading 'log' component
    'preload' => array('log'),

    'import' => array(
        'application.helpers.*',
        'application.models.*',
        'application.components.*',
    ),

    'modules' => array(

        'register',
        'login',
        'forgottenPassword',
        'news',
        'page',
        'cabinet',
        'index',
        'backend',
        'deposit',
        'stats',
        'gallery',
        'install',

    ),

    // application components
    'components' => array(

        'clientScript' => array(
            'scriptMap' => array(
                'jquery.js' => FALSE,
            ),
        ),

        'widgetFactory' => array(

            //'enableSkin' => TRUE,
            //'skinPath' => 'widgets',

            'widgets' => array(

                'CCaptcha' => array(
                    'clickableImage'    => TRUE,
                    'showRefreshButton' => FALSE,
                    'imageOptions'      => array(
                        'title' => 'Refresh',
                    ),
                ),

                // Настройки для виджета пагинации
                'CLinkPager' => array(
                    'header'                => '',
                    'footer'                => '',
                    'hiddenPageCssClass'    => 'disabled',
                    'firstPageLabel'        => '&lt;&lt;',
                    'prevPageLabel'         => '&lt;',
                    'nextPageLabel'         => '&gt;',
                    'lastPageLabel'         => '&gt&gt',
                    'maxButtonCount'        => 7,
                    //'cssFile'             => '/css/pagination.css',
                    'id'                    => 'pagination',
                    'selectedPageCssClass'  => 'active',
                    'htmlOptions'           => array(
                        'class' => 'pagination pagination-sm',
                    )
                    //'internalPageCssClass' => '',
                ),

                // Настройки для виджета breadcrumb
                'CBreadcrumbs' => array(
                    'tagName'               => 'ul',
                    'separator'             => '',
                    'activeLinkTemplate'    => '<li><a href="{url}">{label}</a></li>',
                    'inactiveLinkTemplate'  => '<li>{label}</li>',
                    'htmlOptions'           => array(
                        'class' => 'breadcrumb',
                    ),
                ),
            ),
        ),

        'notify' => array(
            'class' => 'Notify',
        ),

        'config' => array(
            'class' => 'DbConfig',
        ),

        'cache' => array(
            'class' => 'system.caching.CFileCache',
        ),

        'user' => array(
            'class'             => 'WebUser',
            'allowAutoLogin'    => TRUE,
            'autoRenewCookie'   => TRUE,
            'loginUrl'          => array('/login/default/index'),
        ),

        'admin' => array(
            'class'             => 'WebAdmin',
            'allowAutoLogin'    => TRUE,
            'autoRenewCookie'   => TRUE,
            'loginUrl'          => array('/' . $backendUrl . '/login/index'),
        ),

        'authManager' => array(
            'class'         => 'CDbAuthManager',
            'connectionId'  => 'db',
        ),

        'securityManager' => array(
            'cryptAlgorithm' => array('rijndael-256', '', 'ofb', ''),
            'encryptionKey'  => '7fzyTAybk70bzuLf', // Длина ключа должна быть ровно 16, 24 или 32 символа
        ),

        'request' => array(
            'enableCsrfValidation'  => TRUE,
            'csrfTokenName'         => 'GHTWEB_CSRF_TOKEN',
        ),

        'urlManager' => array(
            'urlFormat' => 'path',
            'showScriptName' => FALSE,
            'urlSuffix' => '/',
            'rules' => array(

                // ------------------- [Frontend] -------------------
                '' => 'index/default/index',

                // Deposit (обработка платежа)
                'deposit/result' => 'deposit/default/index',

                // Статические страницы
                'page/<page_name:[a-z0-9-_]+>' => 'page/default/index',

                // Новости
                'news' => 'news/default/index',
                'news/<news_id:\d+>' => 'news/default/detail',

                // Регистрация
                'register' => 'register/default/index',
                'register/<_hash:\w{32}>' => 'register/default/activated',

                // Авторизация
                'login' => 'login/default/index',

                // Восстановление пароля
                'forgotten-password' => 'forgottenPassword/default/index',
                'forgotten-password/<hash:\w{32}>' => 'forgottenPassword/default/step2',

                // Deposit
                'cabinet/deposit' => 'cabinet/deposit/index',
                'cabinet/deposit/processed' => 'cabinet/deposit/processed',
                'deposit/<action:(result|success|fail)>' => 'deposit/default/<action>',
                'cabinet/deposit/sms-list' => 'cabinet/deposit/getSmsNumberList',

                // Logout
                'logout' => 'cabinet/default/logout',


                // Статистика
                'stats/<gs_id:\d+>/<type:([a-z\-]+)>/<clan_id:\w+>' => 'stats/default/index',
                'stats/<gs_id:\d+>/<type:\w+>' => 'stats/default/index',
                'stats/<gs_id:\d+>' => 'stats/default/index',
                'stats' => 'stats/default/index',


                // Cabinet
                'cabinet' => 'cabinet/default/index',

                // Персонажи
                'cabinet/characters' => 'cabinet/characters/index',
                'cabinet/characters/<char_id:([0-9]+)>/<action:(view|teleport)>' => 'cabinet/characters/<action>',

                // Смена пароля от аккаунта
                'cabinet/change-password' => 'cabinet/changePassword/index',

                // Безопасность
                'cabinet/security' => 'cabinet/security/index',

                // Рефералы
                'cabinet/referals' => 'cabinet/referals/index',

                // История платежей
                'cabinet/transaction-history' => 'cabinet/transactionHistory/index',

                // История авторизаций
                'cabinet/auth-history' => 'cabinet/authHistory/index',

                // Магазин
                'cabinet/shop' => 'cabinet/shop/index',
                'cabinet/shop/<category_link:([a-zA-Z0-9\-]+)>' => 'cabinet/shop/category',
                'cabinet/shop/<category_link:([a-zA-Z0-9\-]+)>/buy' => 'cabinet/shop/buy',

                // Тикеты
                'cabinet/tickets' => 'cabinet/tickets/index',
                'cabinet/tickets/add' => 'cabinet/tickets/add',
                'cabinet/tickets/<ticket_id:\d+>/<action:(view|close)>' => 'cabinet/tickets/<action>',

                // Услуги
                'cabinet/services' => 'cabinet/services/index',
                'cabinet/services/premium' => 'cabinet/services/premium',
                'cabinet/services/remove-hwid' => 'cabinet/services/removeHwid',
                /*'cabinet/services/change-char-name' => 'cabinet/services/changeCharName',
                'cabinet/services/change-gender' => 'cabinet/services/changeGender',*/

                // Бонусы
                'cabinet/bonuses' => 'cabinet/bonuses/index',
                'cabinet/bonuses/bonus-code' => 'cabinet/bonuses/bonusCode',
                'cabinet/bonuses/<bonus_id:\d+>/<action:(activation)>' => 'cabinet/bonuses/<action>',

                // Личные сообщения
                'cabinet/messages' => 'cabinet/messages/index',
                'cabinet/messages/<id:\d+>/detail' => 'cabinet/messages/detail',

                // Галерея
                'gallery' => 'gallery/default/index',




                // ------------------- [Backend] -------------------
                $backendUrl => 'backend/default/index',

                // Авторизация
                $backendUrl . '/login' => 'backend/login/index',

                // Выход из админки
                $backendUrl . '/logout' => 'backend/login/logout',

                // Очистка кэша
                $backendUrl . '/clear-cache' => 'backend/default/clearCache',

                // Инфа о item
                $backendUrl . '/get-item-info' => 'backend/default/getItemInfo',

                // Игровые сервера
                $backendUrl . '/game-servers' => 'backend/gameServers/index',
                $backendUrl . '/game-servers/<gs_id:\d+>/edit' => 'backend/gameServers/form',
                $backendUrl . '/game-servers/add' => 'backend/gameServers/form',
                $backendUrl . '/game-servers/<gs_id:\d+>/<action:(del|allow)>' => 'backend/gameServers/<action>',

                // Магазин
                $backendUrl . '/game-servers/<gs_id:\d+>/shop' => 'backend/gameServers/shop',
                $backendUrl . '/game-servers/<gs_id:\d+>/shop/category/<category_id:\d+>/edit' => 'backend/gameServers/shopCategoryForm',
                $backendUrl . '/game-servers/<gs_id:\d+>/shop/category/add' => 'backend/gameServers/shopCategoryForm',
                $backendUrl . '/game-servers/<gs_id:\d+>/shop/category/<category_id:\d+>/allow' => 'backend/gameServers/shopCategoryAllow',
                $backendUrl . '/game-servers/<gs_id:\d+>/shop/category/<category_id:\d+>/del' => 'backend/gameServers/shopCategoryDel',
                $backendUrl . '/game-servers/<gs_id:\d+>/shop/category/<category_id:\d+>/packs' => 'backend/gameServers/shopCategoryPacks',
                $backendUrl . '/game-servers/<gs_id:\d+>/shop/category/<category_id:\d+>/pack/add' => 'backend/gameServers/shopCategoryPacksForm',
                $backendUrl . '/game-servers/<gs_id:\d+>/shop/category/<category_id:\d+>/pack/<pack_id:\d+>/items' => 'backend/gameServers/shopCategoryPackItems',
                $backendUrl . '/game-servers/<gs_id:\d+>/shop/category/<category_id:\d+>/pack/<pack_id:\d+>/create-item' => 'backend/gameServers/shopCategoryPackCreateItem',
                $backendUrl . '/game-servers/<gs_id:\d+>/shop/category/<category_id:\d+>/pack/<pack_id:\d+>/item/<item_id:\d+>/edit' => 'backend/gameServers/shopCategoryPackEditItem',
                $backendUrl . '/game-servers/<gs_id:\d+>/shop/category/<category_id:\d+>/pack/<pack_id:\d+>/item/<item_id:\d+>/del' => 'backend/gameServers/shopCategoryPackDelItem',
                $backendUrl . '/game-servers/<gs_id:\d+>/shop/category/<category_id:\d+>/pack/<pack_id:\d+>/edit' => 'backend/gameServers/shopCategoryPacksForm',

                $backendUrl . '/game-servers/<gs_id:\d+>/shop/category/<category_id:\d+>/pack/<pack_id:\d+>/allow' => 'backend/gameServers/shopCategoryPackAllow',
                $backendUrl . '/game-servers/<gs_id:\d+>/shop/category/<category_id:\d+>/pack/<pack_id:\d+>/del-img' => 'backend/gameServers/shopCategoryPackDelImage',
                $backendUrl . '/game-servers/<gs_id:\d+>/shop/category/<category_id:\d+>/pack/<pack_id:\d+>/del' => 'backend/gameServers/shopCategoryPackDel',


                // Логин сервера
                $backendUrl . '/login-servers' => 'backend/loginServers/index',
                $backendUrl . '/login-servers/<ls_id:\d+>/edit' => 'backend/loginServers/form',
                $backendUrl . '/login-servers/add' => 'backend/loginServers/form',
                $backendUrl . '/login-servers/<ls_id:\d+>/<action:(del|allow|accounts)>' => 'backend/loginServers/<action>',

                // Игровые аккаунты на логине
                // $backendUrl . '/login-servers/<ls_id:\d+>/<action:(edit|del|shop)>' => 'backend/loginServers/<action>',

                // Юзеры
                $backendUrl . '/users' => 'backend/users/index',
                $backendUrl . '/users/add' => 'backend/users/add',
                $backendUrl . '/users/<user_id:\d+>/<action:(view|referals)>' => 'backend/users/<action>',
                $backendUrl . '/users/<user_id:\d+>/auth-history' => 'backend/users/authHistory',
                $backendUrl . '/users/<user_id:\d+>/delete-bonus/<bonus_id:\d+>' => 'backend/users/delBonus',
                $backendUrl . '/users/<user_id:\d+>/add-bonus/' => 'backend/users/addBonus',
                $backendUrl . '/users/<user_id:\d+>/add-message/' => 'backend/users/addMessage',
                $backendUrl . '/users/<user_id:\d+>/item-purchase/' => 'backend/users/itemPurchaseLog',
                $backendUrl . '/users/<user_id:\d+>/transaction-history/' => 'backend/users/transactionHistory',
                $backendUrl . '/users/<user_id:\d+>/edit-data/' => 'backend/users/editData',

                // Transactions
                //$backendUrl . '/transactions/user/<user_id:\d+>' => 'backend/transactions/index',

                // Бонусы
                $backendUrl . '/bonuses/<bonus_id:\d+>/items' => 'backend/bonuses/items',
                $backendUrl . '/bonuses/<bonus_id:\d+>/create-item' => 'backend/bonuses/itemAdd',
                $backendUrl . '/bonuses/<bonus_id:\d+>/item/<item_id:\d+>/edit' => 'backend/bonuses/itemEdit',
                $backendUrl . '/bonuses/<bonus_id:\d+>/item/<item_id:\d+>/del' => 'backend/bonuses/itemDel',
                $backendUrl . '/bonuses/<bonus_id:\d+>/item/<item_id:\d+>/allow' => 'backend/bonuses/itemAllow',
                $backendUrl . '/bonuses/generate-code' => 'backend/bonuses/generateCode',

                $backendUrl . '/bonuses/codes/add' => 'backend/bonuses/codeAdd',
                $backendUrl . '/bonuses/codes/<code_id:\d+>/allow' => 'backend/bonuses/codeAllow',
                $backendUrl . '/bonuses/codes/<code_id:\d+>/edit' => 'backend/bonuses/codeEdit',
                $backendUrl . '/bonuses/codes/<code_id:\d+>/add' => 'backend/bonuses/codeAdd',
                $backendUrl . '/bonuses/codes/<code_id:\d+>/del' => 'backend/bonuses/codeDel',

                // Тикеты
                $backendUrl . '/tickets/<id:\d+>/edit' => 'backend/tickets/edit',
                $backendUrl . '/tickets/categories/<category_id:\d+>/edit' => 'backend/tickets/categoryForm',
                $backendUrl . '/tickets/categories/<category_id:\d+>/del' => 'backend/tickets/categoryDel',
                $backendUrl . '/tickets/categories/<category_id:\d+>/allow' => 'backend/tickets/categoryAllow',
                $backendUrl . '/tickets/categories/add' => 'backend/tickets/categoryForm',

                // Общие правила
                $backendUrl . '/<controller:\w+>' => 'backend/<controller>/index',
                $backendUrl . '/<controller:\w+>/<id:\d+>/edit' => 'backend/<controller>/form',
                $backendUrl . '/<controller:\w+>/add' => 'backend/<controller>/form',
                $backendUrl . '/<controller:\w+>/<id:\d+>/<method:(del|allow)>' => 'backend/<controller>/<method>',
                //$backendUrl . '/<controller:\w+>/<method:(del|edit|allow)>' => 'backend/<controller>/<method>',
            ),
        ),

        // DB
        'db' => require 'database.php',

        'errorHandler' => array(
            'errorAction' => 'index/default/error',
        ),

        'log' => array(
            'class' => 'CLogRouter',
            'routes' => array(
                array(
                    'class' => 'CFileLogRoute',
                    'levels' => 'info, error, warning, vardump',
                ),
                array(
                    'class' => 'CWebLogRoute',
                    'levels' => 'error, warning, trace, notice',
                    'categories' => 'application',
                    'enabled' => FALSE,
                ),
                array(
                    'class' => 'CProfileLogRoute',
                    'levels' => 'profile',
                    'enabled' => FALSE,
                ),
            ),
        ),
    ),

    'params' => array(

        // $_GET параметр отвечающий за реферала
        'cookie_referer_name' => 'ref_id',

        // Версии серверов
        'server_versions' => array(
            'Lucera2'       => 'Lucera 2', // http://lucera2.ru
            'Lucera3_it'    => 'Lucera 3', // http://lucera2.ru
            'Emurt_hf'      => 'emurt HF', // http://emurt.ru/index.php
            'OpenTeam_hf'   => 'OpenTeam HF',
            'L2_dev_hf'     => 'L2 DeV HF', // http://dev.lineage-2.me/
            'R2_core_ep'    => 'R2Core Epilogue', // http://r2core.ru/
            'L2j_server_hf' => 'L2J Server HF', // http://www.l2jserver.com/
            'PainTeamIt'    => 'Pain Team Interlude', // http://pain-team.ru/
            'Pwsoft_it'     => 'Pwsoft Interlude', // http://pwsoft.ru/server/
            'L2Scripts_hf'  => 'L2 Scripts HF', // http://l2-scripts.ru/
            'Rebellion_hf'  => 'Rebellion-team HF', // http://rebellion-team.ru/
            'l2j_dev_hf'    => 'l2j-dev HF', // http://l2j-dev.ru/
            'AsgardDev_hf'  => 'Asgard-Dev HF', // http://asgard-dev.ru/index.php
            'Acis_it'       => 'Acis Iterlude', // http://acis.i-live.eu/
            'L2j_lovely_it' => 'L2j lovely interlude', // http://l2jlovely.net/
        ),

        // Папка куда кидаются картинки
        'uploadPath' => 'uploads',

        // Логирование действий юзера (лучше не включать, создаётся доп. нагрузка на БД)
        'user_actions_log' => FALSE,

        // Типы форумов
        'forum_types' => array('ipb', 'phpbb', 'smf', 'vanilla', 'vBulletin', 'xenForo'),

        // Типы валют
        'currency_symbols' => array(
            'RUB' => Yii::t('main', 'Рубли'),
            'EUR' => Yii::t('main', 'Евро'),
            'USD' => Yii::t('main', 'Доллары'),
        ),

        'languages' => require 'languages.php',

        'l2' => require 'lineage.php',

    ),
);
