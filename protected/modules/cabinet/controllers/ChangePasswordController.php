<?php

class ChangePasswordController extends CabinetBaseController
{
    public function actionIndex()
    {
        $model = new ChangePasswordForm();

        if(isset($_POST['ChangePasswordForm']))
        {
            $model->setAttributes($_POST['ChangePasswordForm']);

            if($model->validate())
            {
                if($model->changePassword())
                {
                    user()->setFlash(FlashConst::MESSAGE_SUCCESS, Yii::t('main', 'Пароль успешно изменен.'));
                    $this->refresh();
                }
                else
                {
                    user()->setFlash(FlashConst::MESSAGE_ERROR, Yii::t('main', 'Произошла ошибка! Попробуйте повторить позже.'));
                }
            }
        }

        $this->render('//cabinet/change-password', array(
            'model' => $model,
        ));
    }
}
