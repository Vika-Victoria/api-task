<?php
/**
 * Created by PhpStorm.
 * User: Victoria
 * Date: 08.11.2019
 * Time: 21:19
 */

namespace app\modules\v1\controllers;

use Yii;
use yii\filters\Cors;
use yii\rest\Controller;
use app\modules\v1\models\Persons;
use app\modules\v1\models\Lists;
use yii\web\Response;

require_once('twitter-api/OAuth.php');
require_once('twitter-api/TwitterAPIExchange.php');
require_once('twitter-api/twitteroauth.php');

class ApiController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['corsFilter'] = [
            'class' => Cors::className(),
            'cors' => [
                'Origin' => '*',
                'Access-Control-Request-Method' => ['GET', 'POST'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Headers' => ['*']
            ]
        ];

        return $behaviors;
    }
    public function init()
    {
        parent::init();
        Yii::$app->response->format = Response::FORMAT_JSON;
    }
    /**
     * Add a member to a list
     * @throws \Exception
     */
    public function actionAdd()
    {
        $requestBody = Yii::$app->request->get();
        $id = $requestBody['id'];
        $user = $requestBody['user'];
        $secret = $requestBody['secret'];

        if (empty($id) || empty($user) || empty($secret)) {
            return $this->throwError('missing parameter');
        }

        if ($secret != $this->verifySecret($id, $user)) {
            return $this->throwError('access denied');
        }
//       save user for DB
        $model = new Persons();
        if ($requestBody['user']) {
            $model->load($requestBody, '');
            $model->save();
        }

        $list = Lists::find()->asArray()->one();

        if ($list != null) {
//            add person
            $settings = $this->getSettings();
            $url = 'https://api.twitter.com/1.1/lists/members/create.json';
            $requestMethod = 'POST';
            $postfields = array(
                'slug' => $list['slug'],
                'owner_screen_name' => $list['owner_screen_name'],
                'screen_name' => $user,
            );
            $twitter = new \TwitterAPIExchange($settings);
            if ($twitter) {
                $twitter->buildOauth($url, $requestMethod)->setPostfields($postfields)->performRequest();
            } else {
                return $this->throwError('internal error');
            }
        } else {
            $settings = $this->getSettings();
            $url = 'https://api.twitter.com/1.1/lists/create.json';
            $requestMethod = 'POST';
            $postfields = array(
                'name' => 'List',
            );
            $twitter = new \TwitterAPIExchange($settings);
            $list = json_decode($twitter->buildOauth($url, $requestMethod)
                ->setPostfields($postfields)
                ->performRequest(), $assoc = true);

            $model = new Lists();
            $model->list_id = $list['id'];
            $model->slug = $list['slug'];
            $model->owner_screen_name = $list['user']['screen_name'];
            $model->save();

            $url = 'https://api.twitter.com/1.1/lists/members/create.json';
            $postfields = array(
                'name' => 'List',
            );
            $twitter = new \TwitterAPIExchange($settings);
            if ($twitter) {
                $twitter->buildOauth($url, $requestMethod)->setPostfields($postfields)->performRequest();
            } else {
                return $this->throwError('internal error');
            }
        }
    }
    /**Get the latest news.
     * @return string|mixed
     * @throws \Exception
     */
    public function actionFeed()
    {
        $requestBody = Yii::$app->request->get();
        $id = $requestBody['id'];
        $secret = $requestBody['secret'];

        if (empty($id) || empty($secret)) {
            return $this->throwError('missing parameter');
        }

        if ($secret != $this->verifySecret($id)) {
            return $this->throwError('access denied');
        }

        $list = Lists::find()->asArray()->one();

        $settings = $this->getSettings();
        $requestMethod = 'GET';
        $url = 'https://api.twitter.com/1.1/lists/statuses.json';
        $getfield = '?list_id=' . $list['list_id'];
        $twitter = new \TwitterAPIExchange($settings);
        if ($twitter) {
            $listTwits = json_decode($twitter->setGetfield($getfield)
                ->buildOauth($url, $requestMethod)
                ->performRequest(), true);
        } else {
            return $this->throwError('internal error');
        }

        $decode = json_decode($listTwits, $assos = true);
        $result = array();
        foreach ($decode as $twit) {
            $result[]['user'] = $twit['user']['name'];
            $result[]['tweet'] = $twit['text'];
            $result[]['hashtags'] = $twit['entities']['hashtags'];
        }
        $request  = json_encode(['feed' => $result]);
        return $request;
    }
    /**
     * Removes the specified member from the list.
     * @throws \Exception
     */
    public function actionRemove()
    {
        $requestBody = Yii::$app->request->get();
        $id = $requestBody['id'];
        $user = $requestBody['user'];
        $secret = $requestBody['secret'];

        if (empty($id) || empty($user) || empty($secret)) {
            return $this->throwError('missing parameter');
        }

        if ($secret != $this->verifySecret($id, $user)) {
            return $this->throwError('access denied');
        }

        $settings = $this->getSettings();
        $url = 'https://api.twitter.com/1.1/lists/members/destroy.json';
        $requestMethod = 'POST';
        $postfields = array(
            'screen_name' => $user
        );
        $twitter = new \TwitterAPIExchange($settings);
        if ($twitter) {
            $twitter->buildOauth($url, $requestMethod)
                ->setPostfields($postfields)
                ->performRequest();
        } else {
            return $this->throwError('internal error');
        }

    }
     /**
     * @param $message
     */
    public function throwError($message)
    {
        $errorMessage = json_encode(['error' => $message]);
        echo (string) $errorMessage;
    }
    /**
     * @param $user
     * @param $secret
     * @return string
     */
    protected function verifySecret($id, $user = '')
    {
        $concat = $id . $user;
        $check = sha1($concat);
        return $check;
    }
    /**
     * @return array
     */
    protected function verbs()
    {
        return [
            'add' => ['get'],
            'remove' => ['get'],
            'feed' => ['get']
        ];
    }
    /**
     * Return settings for AOuth and Twitter
     * @return array
     */
    protected function getSettings()
    {
        $settings = [
            'oauth_access_token' => Yii::$app->params['twitterApiKey'],
            'oauth_access_token_secret' => Yii::$app->params['twitterApiSecret'],
            'customer_key' => Yii::$app->params['twitterAccessToken'],
            'customer_secret' => Yii::$app->params['twitterAccessTokenSecret']
        ];

        return $settings;
    }
}
