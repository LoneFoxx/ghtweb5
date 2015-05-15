<?php

Yii::import('application.modules.cabinet.models.UserBonuses');
Yii::import('application.modules.cabinet.models.Bonuses');
Yii::import('application.modules.cabinet.models.BonusesItems');

class UsersController extends BackendBaseController
{
	public function actionIndex()
	{
        $model = new Users('search');
        $model->unsetAttributes();

        if(isset($_GET['Users']))
        {
            $model->setAttributes($_GET['Users']);
        }

        $dataProvider = $model->search();

		$this->render('//users/index', array(
            'dataProvider' => $dataProvider,
            'model'        => $model,
        ));
	}

    public function actionReferals($user_id)
    {
        $dataProvider = new CActiveDataProvider('Referals', array(
            'criteria' => array(
                'condition' => 't.referer = :referer',
                'params' => array(':referer' => $user_id),
                'with' => array('referalInfo' => array(
                    'with' => 'profile',
                ))
            ),
            'pagination' => array(
                'pageSize' => 20,
                'pageVar' => 'page',
            ),
        ));

        $this->render('//users/referals', array(
            'user'  => Users::model()->findByPk($user_id),
            'dataProvider' => $dataProvider,
        ));
    }

    public function actionAuthHistory($user_id)
    {
        $model = new UsersAuthLogs('search');
        $model->unsetAttributes();

        if(isset($_GET['UsersAuthLogs']))
        {
            $model->setAttributes($_GET['UsersAuthLogs']);
        }

        $dataProvider = $model->search();

        $dataProvider->criteria->mergeWith(array(
            'condition' => 'user_id = :user_id',
            'params'    => array('user_id' => $user_id),
        ));

        $this->render('//users/auth-history', array(
            'user'         => Users::model()->findByPk($user_id),
            'model'        => $model,
            'dataProvider' => $dataProvider,
        ));
    }

    /**
     * Просмотр транзакций юзера
     *
     * @param int $user_id
     */
    public function actionTransactionHistory($user_id)
    {
        Yii::import('application.modules.deposit.extensions.Deposit.Deposit');

        $model = new Transactions('search');
        $model->unsetAttributes();

        if(isset($_GET['Transactions']))
        {
            $model->setAttributes($_GET['Transactions']);
        }

        $dataProvider = $model->search();

        $dataProvider->criteria->mergeWith(array(
            'condition' => 't.user_id = :user_id',
            'params'    => array('user_id' => $user_id),
        ));

        $this->render('//users/transaction-history', array(
            'user'            => Users::model()->findByPk($user_id),
            'model'           => $model,
            'dataProvider'    => $dataProvider,
            'aggregatorsList' => Deposit::getAggregatorsList(),
        ));
    }

    public function actionView($user_id)
    {
        $criteria = new CDbCriteria(array(
            'condition' => 't.user_id = :user_id',
            'params' => array(':user_id' => $user_id),
            'with' => array('bonuses' => array(
                'order' => 'bonuses.created_at DESC',
                'with' => array('bonusInfo' => array(
                    'with' => array('items' => array(
                        'with' => array('itemInfo'),
                    )),
                ))
            )),
        ));

        $model = Users::model()->find($criteria);

        if($model === NULL)
        {
            user()->setFlash(FlashConst::MESSAGE_ERROR, Yii::t('backend', 'Юзер не найден.'));
            $this->redirect(array('index'));
        }

        $this->render('//users/view/index', array(
            'model' => $model,
        ));
    }

    public function loadModel($id)
    {
        $model = Users::model()->findByPk($id);

        if(!$model)
        {
            throw new CHttpException(404, 'The requested page does not exist.');
        }

        return $model;
    }

    /**
     * Удаление бонуса у юзера
     *
     * @param int $user_id
     * @param int $bonus_id
     *
     * @throws Exception
     */
    public function actionDelBonus($user_id, $bonus_id)
    {
        $model = UserBonuses::model()->find('id = :id AND user_id = :user_id', array(':id' => $bonus_id, ':user_id' => $user_id));

        if(!$model)
        {
            throw new Exception(404, Yii::t('backend', 'Бонус не найден'));
        }

        $model->delete();

        $this->ajax['status'] = TRUE;
        $this->ajax['msg'] = Yii::t('backend', 'Бонус <b>:name</b> удален', array(':name' => e($model->bonusInfo->title)));

        echo json_encode($this->ajax);
    }

    /**
     * Добавление бонуса юзеру
     *
     * @param $user_id
     */
    public function actionAddBonus($user_id)
    {
        $model = new UserBonuses();

        if(request()->isPostRequest && isset($_POST['UserBonuses']))
        {
            $model->setScenario(ActiveRecord::SCENARIO_CREATE);
            $model->setAttribute('user_id', $user_id);

            $model->setAttributes($_POST['UserBonuses']);

            if($errors = ActiveForm::validate($model))
            {
                $this->ajax['msg'] = $errors;
            }
            else
            {
                $model->save(FALSE);

                $this->ajax['status'] = TRUE;
                $this->ajax['msg'] = Yii::t('backend', 'Бонус <b>:name</b> добавлен', array(':name' => e($model->bonusesModel->title)));
            }
        }
        else
        {
            // get
            $this->ajax['status'] = TRUE;
            $this->ajax['view']   = $this->renderPartial('//users/view/add-bonus-form', array(
                'model' => $model,
            ), TRUE);
        }

        echo json_encode($this->ajax);
    }

    /**
     * Отправляет сообщение
     *
     * @param $user_id
     */
    public function actionAddMessage($user_id)
    {
        $model = new UserMessages();

        if(request()->isPostRequest && isset($_POST['UserMessages']))
        {
            $model->setScenario(ActiveRecord::SCENARIO_CREATE);
            $model->setAttribute('user_id', $user_id);

            $model->setAttributes($_POST['UserMessages']);

            if($errors = ActiveForm::validate($model))
            {
                $this->ajax['msg'] = $errors;
            }
            else
            {
                $model->save(FALSE);

                $this->ajax['status'] = TRUE;
                $this->ajax['msg'] = Yii::t('backend', 'Сообщение отправлено');
            }
        }
        else
        {
            // get
            $this->ajax['status'] = TRUE;
            $this->ajax['view']   = $this->renderPartial('//users/view/add-message-form', array(
                'model' => $model,
            ), TRUE);
        }

        echo json_encode($this->ajax);
    }

    /**
     * Делает юзера админом
     *
     * @param $user_id
     */
    public function actionChangeRole($user_id)
    {
        $model = $this->loadModel($user_id);

        $model->role = $model->role == Users::ROLE_ADMIN ? Users::ROLE_DEFAULT : Users::ROLE_ADMIN;
        $model->auth_hash = NULL;

        $model->save(FALSE);

        user()->setFlash(FlashConst::MESSAGE_SUCCESS, Yii::t('backend', 'Роль изменена на - :role', array(
            ':role' => $model->getRole(),
        )));

        $this->redirectBack();
    }

    /**
     * Редактирование данных
     *
     * @param $user_id
     */
    public function actionEditData($user_id)
    {
        $userModel = $this->loadModel($user_id);
        $formModel = new EditUserForm();

        $formModel->role            = $userModel->role;
        $formModel->activated       = $userModel->activated;
        $formModel->vote_balance    = $userModel->profile->vote_balance;
        $formModel->balance         = $userModel->profile->balance;
        $formModel->phone           = $userModel->profile->phone;

        if($userModel->profile->protected_ip && is_array($userModel->profile->protected_ip))
        {
            $formModel->protected_ip = implode("\r\n", $userModel->profile->protected_ip);
        }


        if(request()->isPostRequest && isset($_POST['EditUserForm']))
        {
            $formModel->setAttributes($_POST['EditUserForm']);

            if($errors = ActiveForm::validate($formModel))
            {
                $this->ajax['msg'] = $errors;
            }
            else
            {
                $transaction = db()->beginTransaction();

                try
                {
                    $userModel->role                    = $formModel->role;
                    $userModel->activated               = $formModel->activated;
                    $userModel->profile->vote_balance   = $formModel->vote_balance;
                    $userModel->profile->balance        = $formModel->balance;
                    $userModel->profile->phone          = $formModel->phone;
                    $userModel->profile->protected_ip   = $formModel->protected_ip;

                    $userModel->save(FALSE);
                    $userModel->profile->save(FALSE);

                    $transaction->commit();

                    $this->ajax['status'] = TRUE;
                    $this->ajax['msg'] = Yii::t('backend', 'Данные сохранены');
                }
                catch(Exception $e)
                {
                    $transaction->rollback();
                    $this->ajax['msg'] = $e->getMessage();
                }
            }
        }
        else
        {
            // get
            $this->ajax['status'] = TRUE;
            $this->ajax['view']   = $this->renderPartial('//users/view/edit-data-form', array(
                'formModel' => $formModel,
                'userModel' => $userModel,
            ), TRUE);
        }

        echo json_encode($this->ajax);
    }

    /**
     * Купленные предметы в магазине
     *
     * @param int $user_id
     */
    public function actionItemPurchaseLog($user_id)
    {
        $dataProvider = new CActiveDataProvider('PurchaseItemsLog', array(
            'criteria' => array(
                'condition' => 'user_id = :user_id',
                'params' => array(
                    ':user_id' => $user_id,
                ),
                'order' => 't.created_at DESC',
                'with' => array('itemInfo', 'gs'),
            ),
        ));

        $this->render('//users/item-purchase-log', array(
            'user'         => Users::model()->findByPk($user_id),
            'dataProvider' => $dataProvider,
        ));
    }
}
