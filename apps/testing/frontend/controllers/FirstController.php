<?php

namespace apps\testing\frontend\controllers;

class FirstController extends \yii\web\Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }

}
