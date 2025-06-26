<?php

namespace console\controllers;

use yii\console\Controller;
use yii\console\ExitCode;

class GoodsController extends Controller
{

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
    }

    /**
     * 同步商品信息
     * @cron * * * * * php ./yii goods/sync
     * @return void
     */
    public function actionSync()
    {
        echo 'actionSync----testing---------start--------';
        return ExitCode::OK;
    }
}
