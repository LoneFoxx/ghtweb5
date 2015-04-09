<?php
/**
 * @var ChangePasswordController $this
 * @var ChangePasswordForm $model
 */

$title__ = Yii::t('main', 'Смена пароля от аккаунта');
$this->pageTitle = $title__;

$this->breadcrumbs=array($title__);
?>

<?php $form = $this->beginWidget('ActiveForm', array(
    'id' => $this->getId() . '-form',
    'htmlOptions' => array(
        'class' => 'form-horizontal',
    )
)) ?>

    <?php echo $form->errorSummary($model) ?>

    <?php $this->widget('app.widgets.FlashMessages.FlashMessages') ?>

    <div class="form-group">
        <?php echo $form->labelEx($model, 'old_password', array('class' => 'col-lg-3 control-label')) ?>
        <div class="field">
            <?php echo $form->passwordField($model, 'old_password', array('class' => 'form-control')) ?>
            <p class="help-block"><?php echo Yii::t('main', 'От :min до :max символов', array(':min' => Users::PASSWORD_MIN_LENGTH, ':max' => Users::PASSWORD_MAX_LENGTH)) ?></p>
        </div>
    </div>
    <div class="form-group">
        <?php echo $form->labelEx($model, 'new_password', array('class' => 'col-lg-3 control-label')) ?>
        <div class="field">
            <?php echo $form->passwordField($model, 'new_password', array('class' => 'form-control')) ?>
            <p class="help-block"><?php echo Yii::t('main', 'От :min до :max символов', array(':min' => Users::PASSWORD_MIN_LENGTH, ':max' => Users::PASSWORD_MAX_LENGTH)) ?></p>
        </div>
    </div>

    <div class="button-group center">
        <button type="submit" class="button">
            <span><?php echo Yii::t('main', 'Изменить') ?></span>
        </button>
    </div>

<?php $this->endWidget() ?>