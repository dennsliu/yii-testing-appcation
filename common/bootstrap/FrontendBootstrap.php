<?php

namespace common\bootstrap;

use Yii;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\web\ForbiddenHttpException;

class FrontendBootstrap implements BootstrapInterface
{
    public function bootstrap($app)
    {
        //监听应用请求事件
        $app->on(Application::EVENT_BEFORE_REQUEST, function ($req) {
            $pathInfoString = Yii::$app->request->getPathInfo();
            //过滤非法url
            if ($pathInfoString && !filter_var($pathInfoString, FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '/[a-zA-Z0-9_\-\/]$/']])) {
                throw new ForbiddenHttpException('非法操作，防火墙已拒绝', 500);
            }
            //加载权限类
            $urlAuth = Yii::$container->get("common\\bootstrap\\frontend\\UrlAuth");

            //> 3、模块载入
            $pathInfo = explode("/", $urlAuth->urlPath);
            if (empty($pathInfo)) {
                return true;
            }

            //模块存在无需载入
            if (Yii::$app->getModule($pathInfo[0])) {
                return true;
            }
            $moduleName = 'apps' . DIRECTORY_SEPARATOR . $pathInfo[0] . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'Module';
            $modulePath = dirname(Yii::$app->basePath) . DIRECTORY_SEPARATOR . $moduleName . '.php';
            //echo $pathInfo[0] . '------------' . str_ireplace(DIRECTORY_SEPARATOR, '\\', $moduleName);
            //exit;
            if (file_exists($modulePath)) {
                Yii::$app->setModule($pathInfo[0], str_ireplace(DIRECTORY_SEPARATOR, '\\', $moduleName));
            }
            // var_dump(Yii::$app->modules);
            //var_dump(Yii::$app->requestedRoute);
            //var_dump(Yii::$app->request->resolve());
            //exit;
        });
    }
}
