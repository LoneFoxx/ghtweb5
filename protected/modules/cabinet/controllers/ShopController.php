<?php

class ShopController extends CabinetBaseController
{
	public function actionIndex()
	{
        $this->render('//cabinet/shop/index', array(
            'categories' => $this->getCategories(),
        ));
	}

    public function getCategories()
    {
        $gs_id = user()->gs_id;

        $dependency = new CDbCacheDependency("SELECT COUNT(0), SUM(UNIX_TIMESTAMP(updated_at)) FROM {{shop_categories}} WHERE gs_id = :gs_id AND status = :status");
        $dependency->params = array('gs_id' => $gs_id, 'status' => ActiveRecord::STATUS_ON);

        $res = ShopCategories::model()->cache(3600 * 24, $dependency)->opened()->findAll('gs_id = :gs_id', array(':gs_id' => user()->gs_id));

        $categories = array();

        foreach($res as $row)
        {
            $categories[$row['id']] = $row;
        }

        return $categories;
    }

    /**
     * Предметы в категории
     *
     * @param string $category_link
     * @throws CHttpException
     */
    public function actionCategory($category_link)
    {
        $criteria = new CDbCriteria(array(
            'condition' => 'link = :link AND gs_id = :gs_id',
            'params' => array(
                'link'  => $category_link,
                'gs_id' => user()->getGsId(),
            ),
            'scopes' => array('opened'),
        ));

        $categoryModel = ShopCategories::model()->find($criteria);

        if(!$categoryModel)
        {
            throw new CHttpException(404, Yii::t('main', 'Нет данных.'));
        }


        // Наборы и предметы в наборах
        $dataProvider = new CActiveDataProvider('ShopItemsPacks', array(
            'criteria' => new CDbCriteria(array(
                'condition' => 'category_id = :category_id',
                'params'    => array(
                    'category_id' => $categoryModel->getPrimaryKey(),
                ),
                'scopes' => array('opened'),
                'order' => 't.sort',
                'with' => array('items' => array(
                    'scopes' => array('opened'),
                    'order' => 'items.sort',
                    'with' => array('itemInfo'),
                )),
            )),
            'pagination' => array(
                'pageVar'  => 'page',
                'pageSize' => 5,
            ),
        ));


        $this->render('//cabinet/shop/category', array(
            'categories'    => $this->getCategories(),
            'categoryModel' => $categoryModel,
            'dataProvider'  => $dataProvider,
        ));
    }

    /**
     * Покупка предметов
     *
     * @param string $category_link
     *
     * @return void
     */
    public function actionBuy($category_link)
    {
        if(!request()->getIsPostRequest() || (!isset($_POST['pack_id']) || !filter_var($_POST['pack_id'], FILTER_VALIDATE_INT)) && $_POST['char_id'] > 0)
        {
            $this->redirect(array('index'));
        }

        // Предметы не выбраны
        if(!isset($_POST['items']) || !is_array($_POST['items']) || count($_POST['items']) < 1)
        {
            user()->setFlash(FlashConst::MESSAGE_ERROR, Yii::t('main', 'Выберите предметы.'));
            $this->redirectBack();
        }

        // Не выбран персонаж
        if(!isset($_POST['char_id']) || !filter_var($_POST['char_id'], FILTER_VALIDATE_INT) && $_POST['char_id'] > 0)
        {
            user()->setFlash(FlashConst::MESSAGE_ERROR, Yii::t('main', 'Выберите персонажа.'));
            $this->redirectBack();
        }

        $char_id  = (int) $_POST['char_id'];
        $packId   = (int) $_POST['pack_id'];
        $items    = array();

        foreach($_POST['items'] as $item)
        {
            if(!isset($item['id']) || !isset($item['count']))
            {
                continue;
            }

            $items[(int) $item['id']] = (int) $item['count'];
        }

        if(!$items)
        {
            user()->setFlash(FlashConst::MESSAGE_ERROR, Yii::t('main', 'Выберите предметы.'));
            $this->redirectBack();
        }

        // Проверяю есть ли такой раздел
        $category = NULL;

        foreach($this->getCategories() as $row)
        {
            if($row->link == $category_link)
            {
                $category = $row;
                break;
            }
        }

        // Пытаюстся купить в закрытой/несуществующей категории
        if(!$category)
        {
            user()->setFlash(FlashConst::MESSAGE_ERROR, Yii::t('main', 'Покупка невозможна.'));
            $this->redirectBack();
        }

        // Проверяю есть ли такой набор
        $pack = db()->createCommand("SELECT id FROM {{shop_items_packs}} WHERE id = ? AND category_id = ? AND status = ?")
            ->queryRow(TRUE, array($packId, $category->getPrimaryKey(), ActiveRecord::STATUS_ON));

        // Набор не найден
        if(!$pack)
        {
            user()->setFlash(FlashConst::MESSAGE_ERROR, Yii::t('main', 'Покупка невозможна.'));
            $this->redirectBack();
        }

        // Ищю предметы в наборе
        $criteria = new CDbCriteria(array(
            'condition' => 'pack_id = :pack_id',
            'params'    => array(
                'pack_id' => $pack['id'],
            ),
            'scopes' => array('opened'),
            'with'   => array('itemInfo')
        ));

        $criteria->addInCondition('id', array_keys($items));

        $itemsDb = ShopItems::model()->findAll($criteria);

        // Если предметы не найдены
        if(!$itemsDb)
        {
            user()->setFlash(FlashConst::MESSAGE_ERROR, Yii::t('main', 'Покупка невозможна.'));
            $this->redirectBack();
        }


        // Общая сумма
        $totalSum  = 0;
        $itemsInfo = array();

        // Подсчитываю что почём
        foreach($itemsDb as $item)
        {
            $id                 = (int) $item->getPrimaryKey();
            $discount           = (float) $item->discount;
            $cost               = (float) $item->cost;
            $costDiscount       = ShopItems::costAtDiscount($cost, $discount);
            $count              = (int) $item->count;
            $sum                = 0;
            $costPerOne         = (float) $cost / $count;
            $costPerOneDiscount = ShopItems::costAtDiscount($costPerOne, $discount);

            $itemsInfo[$id] = array(
                'id'                    => $id,
                'item_id'               => (int) $item->item_id,
                'cost'                  => $cost,
                'cost_per_one'          => $cost / $count,
                'cost_per_one_discount' => $costPerOneDiscount,
                'discount'              => $discount,
                'name'                  => $item->itemInfo->getFullName(),
                'desc'                  => $item->itemInfo->description,
                'enchant'               => (int) $item->enchant,
            );

            if(($count = $items[$id]) > 0)
            {
                $sum += $count * $costPerOneDiscount;
            }

            $itemsInfo[$id]['total_sum_o'] = $sum;

            if($sum > 1)
            {
                $sum = round($sum, 2);
            }
            else
            {
                $sum = ceil($sum);
            }

            $itemsInfo[$id]['total_sum'] = $sum;
            $itemsInfo[$id]['count'] = $count;

            $totalSum += $sum;
        }

        // Проверка баланса
        if($totalSum > 0 && user()->get('balance') < $totalSum)
        {
            user()->setFlash(FlashConst::MESSAGE_ERROR, Yii::t('main', 'У Вас недостаточно средств на балансе для совершения сделки.'));
            $this->redirectBack();
        }

        // Смотрю персонажа на сервере
        try
        {
            $l2 = l2('gs', user()->getGsId())->connect();

            $charIdFieldName = $l2->getField('characters.char_id');
            $login           = user()->get('login');

            $character = $l2->getDb()->createCommand("SELECT online FROM {{characters}} WHERE account_name = :account_name AND " . $charIdFieldName . " = :char_id LIMIT 1")
                ->bindParam('account_name', $login, PDO::PARAM_STR)
                ->bindParam('char_id', $char_id, PDO::PARAM_INT)
                ->queryRow();

            if(!$character)
            {
                user()->setFlash(FlashConst::MESSAGE_ERROR, Yii::t('main', 'Персонаж на сервере не найден.'));
                $this->redirectBack();
            }

            if($character['online'] != 0)
            {
                user()->setFlash(FlashConst::MESSAGE_ERROR, Yii::t('main', 'Персонаж НЕ должен находится в игре.'));
                $this->redirectBack();
            }

            // Подготавливаю предметы для БД
            $itemsToDb = array();

            foreach($itemsInfo as $item)
            {
                $itemsToDb[] = array(
                    'owner_id' => $char_id,
                    'item_id'  => $item['item_id'],
                    'count'    => $item['count'],
                    'enchant'  => $item['enchant'],
                );
            }

            // Накидываю предмет(ы) в игру
            $res = $l2->multiInsertItem($itemsToDb);

            if($res)
            {
                $userId = user()->getId();

                if($totalSum > 0)
                {
                    db()->createCommand("UPDATE {{user_profiles}} SET balance = balance - :total_sum WHERE user_id = :user_id LIMIT 1")
                        ->execute(array(
                            'total_sum' => $totalSum,
                            'user_id' => $userId,
                        ));
                }

                // Записываю лог о сделке
                $itemsLog = array();
                $itemList = '';

                foreach($itemsDb as $i => $item)
                {
                    $itemId   = $item->getPrimaryKey();
                    $itemList .= ++$i . ') ' . $item->itemInfo->getFullName() . ' x' . $itemsInfo[$itemId]['count'] . ' (' . $itemsInfo[$itemId]['total_sum'] . ' ' . $this->gs->currency_name . ')<br>';

                    $itemsLog[] = array(
                        'pack_id'       => $item->pack_id,
                        'item_id'       => $item->item_id,
                        'description'   => $item->description,
                        'cost'          => $item->cost,
                        'discount'      => $item->discount,
                        'currency_type' => $item->currency_type,
                        'count'         => $itemsInfo[$itemId]['count'],
                        'enchant'       => $item->enchant,
                        'user_id'       => user()->getId(),
                        'char_id'       => $char_id,
                        'gs_id'         => user()->getGsId(),
                        'created_at'    => date('Y-m-d H:i:s'),
                    );
                }

                if($itemsLog)
                {
                    $builder = db()->schema->commandBuilder;
                    $builder->createMultipleInsertCommand('{{purchase_items_log}}', $itemsLog)->execute();
                }

                // Логирую действие юзера
                if(app()->params['user_actions_log'])
                {
                    $log = new UserActionsLog();

                    $log->user_id   = user()->getId();
                    $log->action_id = UserActionsLog::ACTION_DEPOSIT_SUCCESS;
                    $log->params    = json_encode($itemsLog);

                    $log->save(FALSE);
                }

                user()->setFlash(FlashConst::MESSAGE_SUCCESS, Yii::t('main', 'Сделка прошла успешно, Нижеперечисленные предметы в ближайшее время будут зачислены на Вашего персонажа.<br><b>:item_list</b>',
                    array(':item_list' => $itemList)));

                notify()->shopBuyItems(user()->get('email'), array(
                    'items' => $itemsInfo,
                ));

                $this->redirectBack();
            }
        }
        catch(Exception $e)
        {
            user()->setFlash(FlashConst::MESSAGE_ERROR, Yii::t('main', 'Произошла ошибка! Попробуйте повторить позже.'));
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR, 'shop_buy');
            $this->redirectBack();
        }
    }
}