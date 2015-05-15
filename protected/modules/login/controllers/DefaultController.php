<?php

class DefaultController extends FrontendBaseController
{
	public function actionIndex()
	{
        // Если уже авторизован
        if(!user()->isGuest)
        {
            $this->redirect(array('/cabinet/default/index'));
        }

        $model = new LoginForm();

        if(isset($_POST['LoginForm']) && !$model->isBlockedForm() && $model->getGsList())
        {
            $model->setAttributes($_POST['LoginForm']);

            if($model->validate() && $model->login())
            {
                $this->redirect(array('/cabinet/default/index'));
            }
        }

        $this->render('//login', array(
            'model' => $model,
        ));
	}
}