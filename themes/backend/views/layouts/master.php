<?php
$assetsUrl = app()->getAssetManager()->publish(Yii::getPathOfAlias('webroot.themes.' . themeName() . '.assets'), FALSE, -1, YII_DEBUG);

// jQuery
js('//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js');

// Handlebars
js('//cdnjs.cloudflare.com/ajax/libs/handlebars.js/1.3.0/handlebars.min.js');

// Notification
js($assetsUrl . '/js/notification.js', CClientScript::POS_END);
css($assetsUrl . '/css/notifications.css');

// Font
css('//fonts.googleapis.com/css?family=PT+Sans:400,700&subset=latin,cyrillic');

// Bootstrap
css('//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css');
js('//netdna.bootstrapcdn.com/bootstrap/3.1.0/js/bootstrap.min.js', CClientScript::POS_END);

// Themes
css($assetsUrl . '/css/style.css');

js($assetsUrl . '/js/serializeForm.js', CClientScript::POS_END);

js($assetsUrl . '/js/main.js', CClientScript::POS_END);

Yii::import('application.modules.cabinet.models.Tickets');
?>

<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>GHTWEB / Backend</title>

    <script>
    var CSRF_TOKEN_NAME = '<?php echo request()->csrfTokenName ?>',
        CSRF_TOKEN_VALUE = '<?php echo request()->csrfToken ?>',
        APP = {};
    </script>
</head>
<body>

    <nav class="navbar navbar-default navbar-fixed-top" role="navigation">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
        </div>
        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <div class="container">
                <?php $this->widget('zii.widgets.CMenu',array(
                    'encodeLabel' => FALSE,
                    'htmlOptions' => array(
                        'class' => 'nav navbar-nav',
                    ),
                    'items' => array(
                        array(
                            'label' => Yii::t('backend', 'Главная'),
                            'url' => array('/backend/default/index')
                        ),
                        array(
                            'label' => Yii::t('backend', 'Страницы'),
                            'url' => array('/backend/pages/index')
                        ),
                        array(
                            'label' => Yii::t('backend', 'Новости'),
                            'url' => array('/backend/news/index')
                        ),
                        array(
                            'label' => Yii::t('backend', 'Юзеры'),
                            'url' => array('/backend/users/index')
                        ),
                        array(
                            'label' => Yii::t('backend', 'Настройки'),
                            'url' => array('/backend/config/index')
                        ),
                        array(
                            'label' => 'Lineage <b class="caret"></b>',
                            'url' => '#',
                            'submenuOptions' => array( // LI > UL
                                'class' => 'dropdown-menu'
                            ),
                            'itemOptions' => array( // LI
                                'class' => 'dropdown'
                            ),
                            'linkOptions' => array(
                                'class' => 'dropdown-toggle', // LI > A
                                'data-toggle' => 'dropdown',
                            ),
                            'items' => array(
                                array(
                                    'label' => Yii::t('main', 'Игровые сервера'),
                                    'url' => array('/backend/gameServers/index'),
                                ),
                                array(
                                    'label' => Yii::t('main', 'Логин сервера'),
                                    'url' => array('/backend/loginServers/index'),
                                ),
                            ),
                        ),
                        array(
                            'label' => Yii::t('backend', 'Пополнения баланса'),
                            'url' => array('/backend/transactions/index')
                        ),
                        array(
                            'label' => Yii::t('backend', 'Галерея'),
                            'url' => array('/backend/gallery/index')
                        ),
                        array(
                            'label' => Yii::t('main', 'Бонусы') . ' <b class="caret"></b>',
                            'url' => '#',
                            'submenuOptions' => array( // LI > UL
                                'class' => 'dropdown-menu'
                            ),
                            'itemOptions' => array( // LI
                                'class' => 'dropdown'
                            ),
                            'linkOptions' => array(
                                'class' => 'dropdown-toggle', // LI > A
                                'data-toggle' => 'dropdown',
                            ),
                            'items' => array(
                                array(
                                    'label' => Yii::t('main', 'Просмотр'),
                                    'url' => array('/backend/bonuses/index'),
                                ),
                                array(
                                    'label' => Yii::t('main', 'Коды'),
                                    'url' => array('/backend/bonuses/codes'),
                                ),
                            ),
                        ),
                        array(
                            'label' => '<span class="count-tickets label label-primary" rel="tooltip" title="' . Yii::t('backend', 'Новые тикеты') . '">' . Tickets::model()->count('updated_at IS NULL') . '</span>' . Yii::t('main', 'Тикеты') . ' <b class="caret"></b>',
                            'url' => '#',
                            'submenuOptions' => array( // LI > UL
                                'class' => 'dropdown-menu'
                            ),
                            'itemOptions' => array( // LI
                                'class' => 'dropdown'
                            ),
                            'linkOptions' => array(
                                'class' => 'dropdown-toggle', // LI > A
                                'data-toggle' => 'dropdown',
                            ),
                            'items' => array(
                                array(
                                    'label' => Yii::t('main', 'Просмотр'),
                                    'url' => array('/backend/tickets/index'),
                                ),
                                array(
                                    'label' => Yii::t('main', 'Категории'),
                                    'url' => array('/backend/tickets/categories'),
                                ),
                            ),
                        ),
                    )
                )) ?>
            </div>
        </div>
    </nav>

    <div class="wrapper">

        <section class="site-container container">
            <div class="page-header">
                <?php $this->widget('zii.widgets.CBreadcrumbs', array(
                    'links' => $this->breadcrumbs,
                    'homeLink' => '<li>' . CHtml::link(Yii::t('main', 'Главная'), array('/backend/default/index')) . '</li>',
                )) ?>
            </div>
            <?php echo $content ?>
        </section>

        <div class="push"></div>
    </div>

    <footer class="site-footer">
        <div class="container">&copy; <a href="">ghtweb.ru v5</a></div>
    </footer>



    <script type="template" id="modal-box-tpl">
        <div class="modal fade">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                        <h4 class="modal-title">{{{title}}}</h4>
                    </div>
                    <div class="modal-body">{{{body}}}</div>
                </div>
            </div>
        </div>
    </script>

</body>
</html>