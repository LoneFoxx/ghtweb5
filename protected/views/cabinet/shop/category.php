<?php
/**
 * @var ShopController $this
 * @var ShopCategories[] $categories
 * @var ShopCategories $categoryModel
 * @var CActiveDataProvider $dataProvider
 * @var ShopItemsPacks[] $data
 */

$title_ = Yii::t('main', 'Магазин');
$this->pageTitle = $title_;

$this->breadcrumbs = array(
    $title_ => array('/cabinet/shop'),
    $categoryModel->name
);
?>

<?php $this->renderPartial('//cabinet/shop/menu', array('categories' => $categories)) ?>

<?php $characters = user()->getCharacters() ?>

<?php if($data = $dataProvider->getData()) { ?>

    <?php $this->widget('app.widgets.FlashMessages.FlashMessages') ?>

    <div class="entry">
        <div class="scroll-pane">
            <div class="shop">
                <table class="table pack-info-table">
                    <tbody>
                        <?php foreach($data as $i => $pack) { ?>
                            <tr>
                                <td>
                                    <div class="pack-info clearfix">
                                        <?php if($pack->imgIsExists()) { ?>
                                            <figure>
                                                <?php echo CHtml::image($pack->getImgUrl()) ?>
                                            </figure>
                                        <?php } ?>
                                        <div class="info">
                                            <h3><?php echo e($pack->title) ?></h3>
                                            <p class="desc"><?php echo $pack->description ?></p>
                                        </div>
                                    </div>
                                    <?php echo CHtml::beginForm(array('/cabinet/shop/buy', 'category_link' => $categoryModel->link), 'post', array('class' => 'form-inline')) ?>
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th></th>
                                                    <th><?php echo Yii::t('main', 'Название') ?></th>
                                                    <th><?php echo Yii::t('main', 'Кол-во') ?></th>
                                                    <th><?php echo Yii::t('main', 'Заточка') ?></th>
                                                    <th><?php echo Yii::t('main', 'Стоимость') ?></th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($pack->items as $ii => $item) { ?>
                                                    <tr>
                                                        <td><?php echo $item->itemInfo->getIcon() ?></td>
                                                        <td>
                                                            <?php echo e($item->itemInfo->getFullName()) ?>
                                                            <?php echo $item->itemInfo->getGrade() ?>
                                                            <?php if($item->description) { ?>
                                                                <span class="glyphicon glyphicon-question-sign" title="<?php echo e($item->description) ?>" rel="tooltip"></span>
                                                            <?php } ?>
                                                        </td>
                                                        <td><input type="text" name="items[<?php echo $ii ?>][count]" value="<?php echo $item->count ?>" class="form-control"></td>
                                                        <td><?php echo $item->enchant ?></td>
                                                        <td>
                                                            <?php if($item->discount > 0) { ?>
                                                                <?php echo Yii::t('main', 'Скидка') ?>: <?php echo $item->discount ?>%<br>
                                                                <b><?php echo ShopItems::costAtDiscount($item->getCost(), $item->discount) ?></b><br>
                                                                <strike> <?php echo formatCurrency($item->getCost(), FALSE) ?></strike><br>
                                                            <?php } else { ?>
                                                                <?php echo $item->cost ?>
                                                            <?php } ?>

                                                            <?php if($item->currency_type == 'donat') { ?>
                                                                <?php echo config('server.curryncy_name') ?>
                                                            <?php } else { ?>
                                                                <?php echo Yii::t('main', 'Голоса') ?>
                                                            <?php } ?>
                                                        </td>
                                                        <td>
                                                            <?php if($characters) { ?>
                                                                <label for="<?php echo $item->id ?>" class="control">
                                                                    <input id="<?php echo $item->id ?>" type="checkbox" name="items[<?php echo $ii ?>][id]" value="<?php echo $item->id ?>">
                                                                    <span class="switch"></span>
                                                                </label>
                                                            <?php } ?>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                            <?php if($characters) { ?>
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="6">
                                                            <input type="hidden" name="pack_id" value="<?php echo $pack->id ?>">
                                                            <button type="submit" class="button">
                                                                <span><?php echo Yii::t('main', 'Купить выбранные предметы на персонажа') ?></span>
                                                            </button>
                                                            <?php echo CHtml::dropDownList('char_id', '', CHtml::listData($characters, 'char_id', 'char_name'), array('class' => 'form-control')) ?>
                                                        </td>
                                                    </tr>
                                                </tfoot>
                                            <?php } ?>
                                        </table>
                                    <?php echo CHtml::endForm() ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php $this->widget('CLinkPager', array(
        'pages' => $dataProvider->getPagination(),
    )) ?>

<?php } else { ?>

    <div class="alert alert-info">
        <?php echo Yii::t('main', 'Нет данных.') ?>
    </div>

<?php } ?>