<?php

class ServicesController extends CabinetBaseController
{
    public function actionIndex()
    {
        $this->render('//cabinet/services/index');
    }

    public function actionPremium()
    {
        $gs_id = user()->getGsId();
        $data  = array();

        $dependency = new CDbCacheDependency("SELECT MAX(UNIX_TIMESTAMP(updated_at)) FROM {{gs}} WHERE status = :status AND id = :id LIMIT 1");
        $dependency->params = array(
            'id'     => $gs_id,
            'status' => ActiveRecord::STATUS_ON,
        );
        $dependency->reuseDependentData = TRUE;

        $data['gs'] = Gs::model()->cache(3600 * 24, $dependency)->opened()->findByPk($gs_id);

        if($data['gs']['services_premium_allow'])
        {
            try
            {
                // В HF ервере http://emurt.ru/ инфа о ПА находится в логин сервере
                if($data['gs']['version'] == 'Emurt_hf')
                {
                    $l2 = l2('ls', $data['gs']['login_id'])->connect();
                }
                else
                {
                    $l2 = l2('gs', $gs_id)->connect();
                }

                $login = user()->get('login');

                $data['premium'] = $l2->getPremiumInfo($login);

                if(request()->isPostRequest && isset($_POST['period']) && is_numeric($_POST['period']))
                {
                    $premiums = $data['gs']['services_premium_cost'];
                    $sum      = 0;
                    $days     = 0;

                    foreach($premiums as $p)
                    {
                        if($p['days'] == $_POST['period'])
                        {
                            $sum  = $p['cost'];
                            $days = $p['days'];
                        }
                    }

                    if($sum > 0)
                    {
                        if(user()->get('balance') < $sum)
                        {
                            user()->setFlash(FlashConst::MESSAGE_ERROR, Yii::t('main', 'У Вас недостаточно средств на балансе для совершения сделки.'));
                            $this->refresh();
                        }

                        $date = new DateTime();

                        if($data['premium']['dateEnd'] > 0)
                        {
                            $date = new DateTime(date('Y-m-d H:i:s', $data['premium']['dateEnd']));
                        }

                        $date->modify('+' . $_POST['period'] . ' days');

                        $res = $l2->addPremium($login, $date->getTimestamp(), $data['premium']['dateEnd'] > 0);

                        if($res !== FALSE)
                        {
                            $userId = user()->getId();

                            $res = db()->createCommand("UPDATE {{user_profiles}} SET balance = balance - :sum WHERE user_id = :user_id LIMIT 1")
                                ->bindParam('sum', $sum, PDO::PARAM_INT)
                                ->bindParam('user_id', $userId, PDO::PARAM_INT)
                                ->execute();

                            if($res !== FALSE)
                            {
                                // Логирую действие юзера
                                if(app()->params['user_actions_log'])
                                {
                                    $log = new UserActionsLog();

                                    $log->user_id = user()->getId();
                                    $log->action_id = UserActionsLog::ACTION_SERVICES_BUY_PREMIUM;
                                    $log->params = json_encode($data);

                                    $log->save(FALSE);
                                }

                                $dayTranslate = Yii::t('main', '{n} день|{n} дня|{n} дней|{n} дня',  $days);

                                user()->setFlash(
                                    FlashConst::MESSAGE_SUCCESS,
                                    Yii::t('main', 'Вы успешно приобрели "Премиум аккаунт" сроком на <b>:days_translate</b>, дата окончания <b>:date_end</b>.', array(
                                        ':date_end'       => $date->format('Y-m-d H:i'),
                                        ':days_translate' => $dayTranslate,
                                    ))
                                );

                                $this->refresh();
                            }
                        }

                        user()->setFlash(FlashConst::MESSAGE_ERROR, Yii::t('main', 'Произошла ошибка! Попробуйте повторить позже.'));
                        $this->refresh();
                    }
                }
            }
            catch(Exception $e)
            {
                user()->setFlash(FlashConst::MESSAGE_ERROR, Yii::t('main', 'Произошла ошибка! Попробуйте повторить позже.'));
                Yii::log($e->getMessage() . "\n", CLogger::LEVEL_ERROR, 'services_pa');

                $this->redirect(array('index'));
            }
        }

        $this->render('//cabinet/services/premium', $data);
    }

    public function actionRemoveHwid()
    {
        if(request()->isPostRequest)
        {
            try
            {
                $gs = Gs::model()->opened()->findByPk(user()->getGsId());

                $l2 = l2('ls', $gs->login_id)->connect();

                $res = $l2->removeHWID(user()->get('login'));

                if($res > 0)
                {
                    // Логирую действие юзера
                    if(app()->params['user_actions_log'])
                    {
                        $log = new UserActionsLog();

                        $log->user_id = user()->getId();
                        $log->action_id = UserActionsLog::ACTION_SERVICES_REMOVE_HWID;

                        $log->save(FALSE);
                    }

                    user()->setFlash(FlashConst::MESSAGE_SUCCESS, Yii::t('main', 'Привязка к HWID удалена.'));
                }
                else
                {
                    user()->setFlash(FlashConst::MESSAGE_SUCCESS, Yii::t('main', 'Аккаунт не привязан к HWID.'));
                }
            }
            catch(Exception $e)
            {
                user()->setFlash(FlashConst::MESSAGE_ERROR, Yii::t('main', 'Произошла ошибка! Попробуйте повторить позже.'));
                Yii::log($e->getMessage(), CLogger::LEVEL_ERROR, __METHOD__ . ' ' . __LINE__);
            }

            $this->refresh();
        }

        $this->render('//cabinet/services/remove-hwid');
    }

    public function actionChangeCharName()
    {
        $this->render('//cabinet/services/change-char-name');
    }

    public function actionChangeGender()
    {
        $this->render('//cabinet/services/change-gender');
    }
}
