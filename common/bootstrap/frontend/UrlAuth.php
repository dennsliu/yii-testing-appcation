<?php

namespace common\bootstrap\frontend;

use common\ar\oa\AccountIdentityAR;
use common\helpers\JWT;
use phpDocumentor\Reflection\Types\This;
use Yii;
use yii\web\ForbiddenHttpException;

class UrlAuth
{
    /**
     * 访客权限 -- 临时做法
     *
     * @var array
     */
    public $guestAuthUrl = [
        'site/login',
        'site/get-captcha',
        'upload/handle',
        'upload/signed-url',
        'upload/upload-res-url'
    ];

    public $urlPath;

    public $urlArr;

    public function __construct()
    {
        $this->urlPath = Yii::$app->request->getPathInfo() ?: Yii::$app->defaultRoute . '/index';
        //过滤非法url
        if (!filter_var($this->urlPath, FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '/[a-zA-Z0-9_\-\/]$/']])) {
            throw new ForbiddenHttpException('非法操作，防火墙已拒绝', 500);
        }
        $this->urlArr = explode('/', $this->urlPath);
    }

    /**
     * 访问路径是否验证通过
     *
     * @var boolean
     */
    private $_urlCheckRet = false;

    /**
     * 检查url是否需要登录
     *
     * @return void
     */
    public function checkUrl()
    {
        if ($this->_urlCheckRet) {
            return true;
        }
        //校验用户权限逻辑
        if (Yii::$app->getUser()->isGuest) {
            throw new \yii\web\HttpException(401, "用户未登陆或无权限操作");
        }


        return true;
    }

    public function jwtLogin()
    {
        //获取header auth token
        $headers = Yii::$app->request->getHeaders();
        $token = isset($headers['Authorization']) ? $headers['Authorization'] : (isset($headers['token']) ? $headers['token'] : '');
        //判断jwt token是否存在，存在需要进行用户登录操作
        if (empty($token)) {
            //游客无需登录
            return true;
        }
        $token = str_replace('Bearer ', '', $token);
        $jwt = new JWT();
        $tokenData =  $jwt->verifyToken($token);
        if (empty($tokenData) && $this->_urlCheckRet == false) {
            throw new \yii\web\HttpException(401, "用户未登陆");
        }
        //非内部用户可以登录外部服务
        if (isset($tokenData['type']) && $tokenData['type'] != 0) {
            //登录用户信息
            if (Yii::$app->getUser()->login(AccountIdentityAR::findIdentity($tokenData['id']))) {
                Yii::$app->getUser()->identity->userInfo = json_decode(\Yii::$app->cache->get($token), true);
                return true; //成功
            }
        }
        //exit(json_encode(['err' => -1, 'msg' => '登录已失效!']));
    }
}
