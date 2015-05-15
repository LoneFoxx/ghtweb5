<?php
/**
 * @var DefaultController $this
 */

$this->pageTitle = Yii::t('main', 'Личный кабинет');
?>

<h2 class="title user"><?php echo Yii::t('main', 'Здравствуй, :user_name', array(':user_name' => user()->get('login'))) ?>!</h2>

<div class="user-info">
	<p class="gold"><?php echo Yii::t('main', '<span>Ваш баланс —</span> :balance', array(':balance' => '<b>' . formatCurrency(user()->get('balance')) . '</b>')) ?></p>
	<p class="calendar"><?php echo Yii::t('main', '<span>Дата регистрации —</span> :date', array(':date' => '<b>' . date('Y-m-d H:i', strtotime(user()->created_at)) . '</b>')) ?></p>
</div>