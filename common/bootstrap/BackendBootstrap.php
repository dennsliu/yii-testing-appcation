<?php

namespace common\bootstrap;

use Yii;
use yii\base\Application;
use yii\base\BootstrapInterface;

class BackendBootstrap implements BootstrapInterface
{
    public function bootstrap($app)
    {
        //监听应用请求事件
        $app->on(Application::EVENT_BEFORE_REQUEST, function ($req) {
            //加载权限类
            $urlAuth = Yii::$container->get("common\bootstrap\backend\UrlAuth");
            //> 1、jwt用户登录
            $urlAuth->login();
            //> 2、权限校验
            $urlAuth->checkUrl();
            //> 3、模块载入
            $pathInfo = explode("/", $urlAuth->urlPath);
            if (empty($pathInfo)) {
                return true;
            }
            //模块存在无需载入
            if (Yii::$app->getModule($pathInfo[0])) {
                return true;
            }
            $moduleName = 'apps' . DIRECTORY_SEPARATOR . $pathInfo[0] . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'Module';
            $modulePath = dirname(Yii::$app->basePath) . DIRECTORY_SEPARATOR . $moduleName . '.php';
            if (file_exists($modulePath)) {
                Yii::$app->setModule($pathInfo[0], str_ireplace(DIRECTORY_SEPARATOR, '\\', $moduleName));
            }
        });
    }
}
