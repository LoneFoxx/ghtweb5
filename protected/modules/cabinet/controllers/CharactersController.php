<?php

class CharactersController extends CabinetBaseController
{
	public function actionIndex()
	{
        $cacheName = strtr(CacheNames::CABINET_CHARACTER_LIST, array(':gs_id' => user()->getGsId(), ':user_id' => user()->getId()));

        if(!($data = cache()->get($cacheName)))
        {
            $data = array();
            $data['error'] = FALSE;
            $data['characters'] = array();

            try
            {
                $l2 = l2('gs', user()->gs_id)->connect();

                $command = $l2->getDb()->createCommand();
                $command->where('account_name = :account_name', array(':account_name' => user()->get('login')));

                $data['characters'] = $l2->characters($command)->queryAll();

                cache()->set($cacheName, $data, 600);
            }
            catch(Exception $e)
            {
                $data['error'] = Yii::t('main', $e->getMessage());
            }
        }

        $this->render('//cabinet/characters/index', $data);
	}

    public function actionView($char_id)
    {
        $cacheName = strtr(CacheNames::CABINET_CHARACTER_VIEW, array(':gs_id' => user()->getGsId(), ':user_id' => user()->getId(), ':char_id' => $char_id));

        if(($data = cache()->get($cacheName)) === FALSE)
        {
            $data = array();
            $data['error'] = FALSE;
            $data['character'] = array();
            $data['items'] = array();

            try
            {
                $l2 = l2('gs', user()->gs_id)->connect();

                $charIdFieldName = $l2->getField('characters.char_id');

                $command = $l2->getDb()->createCommand();
                $command->where('account_name = :account_name AND ' . $charIdFieldName . ' = :char_id', array(':account_name' => user()->get('login'), ':char_id' => $char_id));

                $data['character'] = $l2->characters($command)->queryRow();

                // Предметы
                $command = $l2->getDb()->createCommand();
                $command->where('owner_id = :owner_id', array(':owner_id' => $char_id));

                $res = $l2->items($command)->queryAll();

                if($res)
                {
                    $itemsIds = array();

                    foreach($res as $item)
                    {
                        $itemsIds[] = $item['item_id'];
                        $data['items'][$item['item_id']] = $item;
                    }

                    unset($res);

                    if($itemsIds)
                    {
                        $itemsInfoRes = db()->createCommand("SELECT * FROM `{{all_items}}` WHERE `item_id` IN(" . implode(',', $itemsIds) . ")")->queryAll();
                        $itemsInfo = array();

                        if($itemsInfoRes)
                        {
                            foreach($itemsInfoRes as $item)
                            {
                                $itemsInfo[$item['item_id']] = $item;
                            }

                            unset($itemsInfoRes);

                            foreach($data['items'] as $k => $item)
                            {
                                if(isset($itemsInfo[$item['item_id']]))
                                {
                                    $data['items'][$k] = array_merge($item, $itemsInfo[$item['item_id']]);
                                }
                            }

                            unset($itemsInfo);
                        }

                        unset($itemsInfoRes);
                    }
                }

                cache()->set($cacheName, $data, 300);
            }
            catch(Exception $e)
            {
                $data['error'] = Yii::t('main', $e->getMessage());
            }
        }



        // Если персонаж не найден
        if(!$data['character'])
        {
            $this->redirect(array('index'));
        }


        $this->render('//cabinet/characters/view', $data);
    }

    public function actionTeleport($char_id)
    {
        $statusOn   = ActiveRecord::STATUS_ON;
        $gsId       = user()->getGsId();
        $cache      = new CFileCache();

        $cache->init();

        $dependency = new CDbCacheDependency("SELECT MAX(UNIX_TIMESTAMP(updated_at)) FROM {{gs}} WHERE id = :id AND status = :status LIMIT 1");
        $dependency->params = array('id' => $gsId, 'status' => $statusOn);

        $gsInfo = db()->cache(3600 * 24, $dependency)->createCommand("SELECT * FROM {{gs}} WHERE id = :id AND status = :status LIMIT 1")
            ->bindParam('status', $statusOn, PDO::PARAM_INT)
            ->bindParam('id', $gsId, PDO::PARAM_INT)
            ->queryRow();

        if($gsInfo === FALSE || !$gsInfo['allow_teleport'])
        {
            user()->setFlash(FlashConst::MESSAGE_ERROR, Yii::t('main', 'Телепортация отключена.'));
            $this->redirect(array('index'));
        }

        $userId = user()->getId();

        $cacheName = strtr(CacheNames::CHARACTER_TELEPORT, array(':user_id' => $userId, ':char_id' => $char_id, ':gs_id' => $gsId));

        $teleportsInfo = $cache->get($cacheName);

        if($teleportsInfo !== FALSE)
        {
            user()->setFlash(FlashConst::MESSAGE_ERROR, Yii::t('main', 'Вы уже недавно были телепортированы в :city.', array(':city' => '<b>' . $teleportsInfo['city'] . '</b>')));
            $this->redirect(array('index'));
        }

        try
        {
            $l2 = l2('gs', $gsId)->connect();

            $charIdFieldName = $l2->getField('characters.char_id');

            $command = $l2->getDb()->createCommand();

            $command->where($charIdFieldName . ' = :char_id AND account_name = :account_name', array(':char_id' => $char_id, ':account_name' => user()->get('login')));

            $character = $l2->characters($command)->queryRow();

            if($character === FALSE)
            {
                user()->setFlash(FlashConst::MESSAGE_ERROR, Yii::t('main', 'Персонаж не найден.'));
                $this->redirect(array('index'));
            }

            // Если в игре
            if($character['online'] != 0)
            {
                user()->setFlash(FlashConst::MESSAGE_ERROR, Yii::t('main', 'Персонаж в игре.'));
                $this->redirect(array('index'));
            }

            $city       = Lineage::getRandomCity();
            $userName   = user()->get('login');
            $x          = $city['coordinates'][0]['x'];
            $y          = $city['coordinates'][0]['y'];
            $z          = $city['coordinates'][0]['z'];

            // Телепорт игрока
            $res = $l2->getDb()->createCommand("UPDATE {{characters}} SET x = :x, y = :y, z = :z WHERE " . $charIdFieldName . " = :char_id AND account_name = :account_name LIMIT 1")
                ->bindParam('x', $x, PDO::PARAM_STR)
                ->bindParam('y', $y, PDO::PARAM_STR)
                ->bindParam('z', $z, PDO::PARAM_STR)
                ->bindParam('char_id', $char_id, PDO::PARAM_INT)
                ->bindParam('account_name', $userName, PDO::PARAM_STR)
                ->execute();

            if($res)
            {
                $cache->set($cacheName, array(
                    'char_id'   => $char_id,
                    'city'      => $city['name'],
                ), $gsInfo['teleport_time'] * 60);

                // Логирую действие юзера
                if(app()->params['user_actions_log'])
                {
                    $log = new UserActionsLog();

                    $log->user_id = user()->getId();
                    $log->action_id = UserActionsLog::ACTION_TELEPORT_TO_TOWN;
                    $log->params = json_encode($city);

                    $log->save(FALSE);
                }

                user()->setFlash(FlashConst::MESSAGE_SUCCESS, Yii::t('main', 'Персонаж был телепортрован в :city.', array(':city' => '<b>' . $city['name'] . '</b>')));
                $this->redirect(array('index'));
            }
            else
            {
                user()->setFlash(FlashConst::MESSAGE_ERROR, Yii::t('main', 'Произошла ошибка! Попробуйте повторить позже.'));
                $this->redirect(array('index'));
            }
        }
        catch(Exception $e)
        {
            user()->setFlash(FlashConst::MESSAGE_ERROR, Yii::t('main', 'Произошла ошибка! Попробуйте повторить позже.'));
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR, __METHOD__ . ' ' . __LINE__);
            $this->redirect(array('index'));
        }
    }
}