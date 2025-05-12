<?php

namespace apps\testing\frontend\controllers;

use yii\web\Controller;

class DefaultController extends Controller
{
    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
        exit('-=-=-=-=-=');
        return $this->render('index');
    }
}
