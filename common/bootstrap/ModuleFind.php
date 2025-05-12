<?php

namespace common\bootstrap;

use Yii;

class ModuleFind
{
    /**
     * 获取模块是否存在
     *
     * @param [type] $moduleId 模块ID 
     * @param string $endpoint 
     * @return void
     */
    public function getModuleInfo($moduleId, $endpoint = 'backend')
    {
        $modulePath = dirname(Yii::$app->getBasePath()) . DIRECTORY_SEPARATOR . 'apps' . DIRECTORY_SEPARATOR . $moduleId . DIRECTORY_SEPARATOR . $endpoint;
        if (is_dir($modulePath)) {
            Yii::$app->setModule($moduleId, "\\apps\\{$moduleId}\\{$endpoint}\\Module");
            return true;
        }
        return false;
    }
}
