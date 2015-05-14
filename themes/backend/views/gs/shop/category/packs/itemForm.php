<?php
/**
 * @var GameServersController $this
 * @var Gs $gs
 * @var ShopCategories $category
 * @var ShopItemsPacks $pack
 * @var ShopItems[] $model
 */

$assetsUrl = app()->getAssetManager()->publish(Yii::getPathOfAlias('webroot.themes.' . themeName() . '.assets'), FALSE, -1, YII_DEBUG);

js($assetsUrl . '/js/typeahead.bundle.min.js', CClientScript::POS_END);
js($assetsUrl . '/js/search-items.js', CClientScript::POS_END);

$title__ = Yii::t('backend', 'Наборы');
$this->pageTitle = $title__;
$this->breadcrumbs = array(
    Yii::t('backend', 'Сервера') => array('/backend/gameServers/index'),
    $gs->name . ' - ' . Yii::t('backend', 'Магазин') => array('/backend/gameServers/shop', 'gs_id' => $gs->id),
    Yii::t('backend', 'Наборы для категории - :category_name', array(':category_name' => $category->name)) => array('/backend/gameServers/shopCategoryPacks', 'gs_id' => $gs->id, 'category_id' => $category->id),
    Yii::t('backend', 'Предметы в наборе - :pack_name', array(':pack_name' => $pack->title)) => array('/backend/gameServers/shopCategoryPackItems',  'gs_id' => $gs->id, 'category_id' => $category->id, 'pack_id' => $pack->id),
    ($this->getAction()->id == 'shopCategoryPackCreateItem' ? Yii::t('backend', 'Добавление предмета') : Yii::t('backend', 'Редактирование предмета')),
) ?>

<script>
    var urlItemInfo = '<?php echo $this->createUrl('/backend/default/getItemInfo') ?>';
</script>

<?php $form = $this->beginWidget('ActiveForm', array(
    'id' => $this->getId() . '-form',
    'htmlOptions' => array(
        'class'   => 'form-horizontal',
    )
)) ?>

    <?php echo $form->errorSummary($model) ?>

    <?php $this->widget('app.widgets.FlashMessages.FlashMessages') ?>

    <style>
    .img-block
    {
        position: relative;
    }
        .img-block .img
        {
            position: absolute;
            top: 0;
            right: -20px;
            width: 32px;
            height: 32px;
        }
    </style>

    <p class="help-block" style="font-size: 13px;"><b style="color: red;">*</b> <?php echo Yii::t('backend', 'Чтобы добавить предмет достаточно начать набирать его название или ввести его ID') ?></p><br>

    <input name="old_item_id" value="<?php echo $model->item_id ?>" type="hidden"/>

    <div class="form-group">
        <?php echo $form->labelEx($model, 'item_name', array('class' => 'col-lg-3 control-label')) ?>
        <div class="col-lg-9 img-block">
            <div class="img">
                <?php if($this->getAction()->id != 'shopCategoryPackCreateItem') { ?>
                    <?php echo $model->itemInfo->getIcon() ?>
                <?php } ?>
            </div>
            <?php echo $form->textField($model, 'item_name', array('placeholder' => $model->getAttributeLabel('item_name'), 'class' => 'form-control js-item-name')) ?>
            <p class="help-block"><?php echo Yii::t('backend', 'Название пишется по английски (База синхронизирована с Euro клиентом)') ?></p>
        </div>
    </div>
    <div class="form-group">
        <?php echo $form->labelEx($model, 'item_id', array('class' => 'col-lg-3 control-label')) ?>
        <div class="col-lg-9">
            <?php echo $form->textField($model, 'item_id', array('placeholder' => $model->getAttributeLabel('item_id'), 'class' => 'form-control js-item-id')) ?>
            <p class="help-block"><?php echo Yii::t('backend', '57 - Адена, 4037 - Coin of luck') ?></p>
        </div>
    </div>
    <div class="form-group">
        <?php echo $form->labelEx($model, 'description', array('class' => 'col-lg-3 control-label')) ?>
        <div class="col-lg-9">
            <?php echo $form->textArea($model, 'description', array('placeholder' => $model->getAttributeLabel('description'), 'class' => 'form-control')) ?>
        </div>
    </div>
    <div class="form-group">
        <?php echo $form->labelEx($model, 'cost', array('class' => 'col-lg-3 control-label')) ?>
        <div class="col-lg-9">
            <?php echo $form->textField($model, 'cost', array('placeholder' => $model->getAttributeLabel('cost'), 'class' => 'form-control')) ?>
            <p class="help-block"><?php echo Yii::t('backend', 'Сколько <b>:name</b> отдадут за кол-во предметов (кол-во указывается ниже).', array(':name' => $gs->getCurrencyName())) ?></p>
        </div>
    </div>
    <div class="form-group">
        <?php echo $form->labelEx($model, 'discount', array('class' => 'col-lg-3 control-label')) ?>
        <div class="col-lg-9">
            <?php echo $form->textField($model, 'discount', array('placeholder' => $model->getAttributeLabel('discount'), 'class' => 'form-control')) ?>
            <p class="help-block"><?php echo Yii::t('backend', 'Вводите только число, без знака %.') ?></p>
        </div>
    </div>
    <div class="form-group">
        <?php echo $form->labelEx($model, 'count', array('class' => 'col-lg-3 control-label')) ?>
        <div class="col-lg-9">
            <?php echo $form->textField($model, 'count', array('placeholder' => $model->getAttributeLabel('count'), 'class' => 'form-control')) ?>
        </div>
    </div>
    <div class="form-group">
        <?php echo $form->labelEx($model, 'enchant', array('class' => 'col-lg-3 control-label')) ?>
        <div class="col-lg-9">
            <?php echo $form->textField($model, 'enchant', array('placeholder' => $model->getAttributeLabel('enchant'), 'class' => 'form-control')) ?>
        </div>
    </div>
    <div class="form-group">
        <?php echo $form->labelEx($model, 'sort', array('class' => 'col-lg-3 control-label')) ?>
        <div class="col-lg-9">
            <?php echo $form->textField($model, 'sort', array('placeholder' => $model->getAttributeLabel('sort'), 'class' => 'form-control')) ?>
        </div>
    </div>
    <div class="form-group">
        <?php echo $form->labelEx($model, 'status', array('class' => 'col-lg-3 control-label')) ?>
        <div class="col-lg-9">
            <?php echo $form->dropDownList($model, 'status', $model->getStatusList(), array('class' => 'form-control')) ?>
        </div>
    </div>
    <div class="form-group">
        <div class="col-sm-offset-3 col-sm-9">
            <button type="submit" class="btn btn-primary"><?php echo ($this->getAction()->id == 'shopCategoryPackCreateItem' ? Yii::t('backend', 'Создать') : Yii::t('backend', 'Сохранить')) ?></button>
            <?php echo CHtml::link(Yii::t('backend', 'назад'), array('/backend/gameServers/shopCategoryPacks', 'gs_id' => $gs->id, 'category_id' => $category->id)) ?>
        </div>
    </div>

<?php $this->endWidget() ?>