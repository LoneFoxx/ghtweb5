<?php
/**
 * @var array $items
 */

$totalSum = 0;
?>

<font color="#ead255" face="Trebuchet MS" style="font-size: 24px;"><?php echo Yii::t('main', 'Здравствуйте!') ?></font><br><br><br><br>
<?php echo Yii::t('main', 'Вы только что совершили покупку в нашем магазине.') ?><br><br>

<?php if($items) { ?>
    <table style="color: #FFFFFF;" width="100%">
        <thead>
            <tr>
                <th>#</th>
                <th><?php echo Yii::t('main', 'Название') ?></th>
                <th><?php echo Yii::t('main', 'Кол-во') ?></th>
                <th><?php echo Yii::t('main', 'Заточка') ?></th>
                <th><?php echo Yii::t('main', 'Скидка') ?></th>
                <th><?php echo Yii::t('main', 'Итоговая цена') ?></th>
            </tr>
        </thead>
        <?php $i = 1; foreach($items as $item) { ?>
            <?php $totalSum += $item['total_sum'] ?>
            <tr>
                <td><?php echo $i++ ?></td>
                <td><?php echo e($item['name']) ?></td>
                <td><?php echo $item['count'] ?></td>
                <td><?php echo $item['enchant'] ?></td>
                <td><?php echo $item['discount'] ?>%</td>
                <td><?php echo $item['cost_per_one_discount'] * $item['count'] ?></td>
            </tr>
        <?php } ?>
        <tfoot>
            <td colspan="6"><?php echo Yii::t('main', 'Итого') ?>: <?php echo $totalSum ?></td>
        </tfoot>
    </table>
<?php } ?>

<br>
<?php echo Yii::t('main', 'Спасибо за Вашу помощь в развитии проекта.') ?>