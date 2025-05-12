<?php

namespace common\bootstrap\backend;

use apps\user\backend\common\models\User;
use common\helpers\JWT;
use yii\web\ForbiddenHttpException;
use Yii;

class UrlAuth
{
    /**
     * 访客权限 - 无需登录
     *
     * @var array
     */
    public $guestAuthUrl = [
        'user/login/index',
        'website/internal-api/get-page',
        'fb/qy-auth/*',
        'fb/default/*',
        'gii/*',
        'debug/*',
        'wordpress/webhooks/*',
    ];

    /**
     * 登录后基础权限
     *
     * @var array
     */
    public $baseAuthUrl = [
        'user/login/index',
        'user/staff/role-list',
        'user/staff/all-list',
        'website/default/my-list',
        'website/pixel/my-list',
        'website/pixel/test',
        'add/event/get-field',
        'add/event/index',
        'add/report/*',
        'fb/bm-account/get-my-add-account',
        'fb/bm-account/get-my-ad-account',
        'website/data-source/get-list',
        'od/shipping/tracking'
    ];

    public $urlPath;

    public $urlArr;

    public function __construct()
    {
        $this->urlPath = trim(Yii::$app->request->getPathInfo(), '/') ?: Yii::$app->defaultRoute . '/index';
        // 过滤非法url
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

    public function validUrl($url, $type = 'baseAuthUrl')
    {
        $createUrl = [];
        $unionUrl = '/';
        foreach ($url as $val) {
            if (empty($val)) {
                continue;
            }
            $unionUrl .= $val . '/';
            $createUrl[] = trim($unionUrl, '/');
            $createUrl[] = ltrim($unionUrl, '/') . '*';
        }
        $this->_urlCheckRet = false;
        foreach ($createUrl as $url) {
            if (in_array($url, $this->$type, true)) {
                $this->_urlCheckRet = true;
                break;
            }
        }
    }

    /**
     * 验证游客权限
     *
     * @return void
     */
    public function checkGuest()
    {
        $this->validUrl($this->urlArr, 'guestAuthUrl');
        if ($this->_urlCheckRet && false == empty($this->guestAuthUrl)) {
            return true;
        }
    }

    /**
     * 验证用户权限
     *
     * @return void
     */
    public function checkUser()
    {
        $cacheData = Yii::$app->cache->get('token_' . Yii::$app->getUser()->getId());
        // 判断token是否过期
        if (empty($cacheData)) {
            throw new \yii\web\HttpException(401, '你还没有登录，请登录');
        }

        // 超级管理员无需验证权限
        if (Yii::$app->cache->get('token_' . Yii::$app->getUser()->getId())['user']['is_admin'] == 1) {
            return true;
        }
        $this->validUrl($this->urlArr, 'baseAuthUrl');
        if ($this->_urlCheckRet && false == empty($this->baseAuthUrl)) {
            return true;
        }

        // print_r(
        //     $cacheData['auth_list']
        // );
        // exit;
        // 校验用户权限
        if (isset($cacheData['auth_list']) && false == empty($cacheData['auth_list']) && in_array($this->urlPath, $cacheData['auth_list'])) {
            return true;
        }
        return false;
    }

    /**
     * 登录
     *
     * @return void
     */
    public function login()
    {
        // 获取header auth token
        $headers = Yii::$app->request->getHeaders();
        $token = isset($headers['Authorization']) ? $headers['Authorization'] : '';
        // 判断jwt token是否存在，存在需要进行用户登录操作
        if (empty($token)) {
            return true;
        }

        $token = str_replace('Bearer ', '', $token);
        $jwt = new JWT();
        $tokenData = $jwt->verifyToken($token);
        if (empty($tokenData)) {
            throw new \yii\web\HttpException(401, '登录态已失效，请重新登录');
        }
        $cacheData = Yii::$app->cache->get('token_' . $tokenData['id']);
        // 判断token是否过期
        if (empty($tokenData) || empty($cacheData)) {
            throw new \yii\web\HttpException(401, '登录态缓存已失效，请重新登录');
        }
        $user = User::findIdentity($tokenData['id']);
        if (empty($user)) {
            throw new \yii\web\HttpException(401, '账号已被禁用，请联系管理员');
        }
        if (false == Yii::$app->getUser()->login($user)) {
            throw new \yii\web\HttpException(401, '账号异常，请联系管理员');
        }
        // 更新token过期时间
        // Yii::$app->cache->set('token_' . $tokenData['id'], $cacheData, 3600);
        return true;
    }

    /**
     * 检查url是否需要登录
     *
     * @return void
     */
    public function checkUrl()
    {
        if ($this->checkGuest()) {
            return true;
        }

        if ($this->checkUser()) {
            return true;
        }
        throw new \yii\web\HttpException(403, '您没有权限操作');
    }
}
