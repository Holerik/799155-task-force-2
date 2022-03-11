<?php

namespace app\models;

use Yii;
use yii\base\Model;

class Client extends Model
{
    public $source;
    public $sourceId;
    public $email;
    public $city;
    public $name;
    public $photo;
    public $bdate;
    public $check;

    public function __construct($client)
    {
        $attributes = $client->getUserAttributes();
        $this->source = $client->getId();
        $this->sourceId = $attributes['user_id'];
        $this->email = $attributes['email'];
        $this->city = $attributes['city']['title'];
        $this->name = $attributes['first_name'];
        $time = strtotime($attributes['bdate']);
        $this->bdate = date('Y-m-d', $time);
        $this->photo = $attributes['photo'];
        $this->check = 0;
    }

    public function attributeLabels()
    {
        return [
            'source' => 'vkontakte',
            'sourceId' => 'ID VKontakte',
            'email' => 'email',
            'city' => 'Город',
            'name' => 'Имя пользователя',
            'photo' => 'avatar',
            'bdate' => 'день рождения',
            'check' => 'эагрузить фото',
        ];
    }

    public function rules()
    {
        return [
            [['source', 'sourceId', 'email', 'city'], 'safe'],
            [['name', 'photo', 'bdate', 'check'], 'safe'],
            [['check', 'sourceId'], 'integer'],
            [['source', 'email', 'city'], 'string'],
            ['bdate', 'date'],
        ];
    }

    public function authorizeClient()
    {
        $auth = Source::find()->where(
            [
                'source' => $this->source,
                'source_id' => $this->sourceId,
            ]
        )->one();
        if (Yii::$app->user->isGuest) {
            if ($auth) { //авторизация
                $user = User::findOne($auth->user_id);
                $model = new Logon();
                $model->logon($user, true);
            } else { //регистрация
                if (!empty($this->email) &&
                    User::find()->where(['email' => $this->email])->exists()) {
                        $message = 'Пользователь с такой электронной почтой как в ' . $this->source;
                        $message .= ' уже существует';
                        Yii::$app->getSession()->setFlash('info', $message);
                } else {
                    $geoData = null;
                    if (!empty($this->city)) {
                        $geoData = Location::getGeoData($this->city);
                    }
                    $props = [
                        'name' => $this->name,
                        'email' => $this->email,
                        'password' => Yii::$app->security->generateRandomString(6),
                        'contractor' => 0,
                        'city_id' => $geoData['id'] ?? 0,
                    ];
                    $user = new User();
                    $user->attributes = $props;
                    if ($user->save()) {
                        $profile = new Profile();
                        $profile->user_id = $user->id;
                        $profile->messenger = 'VK' . $this->sourceId;
                        $profile->last_act = date("Y-m-d H:i:s");
                        if (!empty($this->city)) {
                            $profile->city = $this->city;
                        }
                        if (!empty($this->bdate)) {
                            $time = strtotime($this->bdate);
                            $profile->born_date = date('Y-m-d', $time);
                        }
                        if (!empty($this->photo)) {
                            $fileData = explode('?', $this->photo);
                            $fileInfo = explode('/', $fileData[0]);
                            $fileName = $fileInfo[count($fileInfo) - 1];
                            $fileExt = strstr($fileName, '.');
                            $fileName = uniqid('up') . $fileExt;
                            $res = file_get_contents($attributes['photo']);
                            if ($res !== false) {
                                $handle = fopen(
                                    Yii::$app->basePath . '/web' . Yii::$app->params['uploadPath'] . $fileName,
                                    'w'
                                );
                                if ($handle !== false) {
                                    fwrite($handle, $res);
                                    fclose($handle);
                                }
                                $profile->avatar = Yii::$app->params['uploadPath'] . $fileName;
                            }
                        }
                        if (!$profile->save()) {
                            $message = 'Не удалось сохранить профиль. Ошибка: ';
                            $message .= Yii::$app->helpers->getFirstErrorString($profile);
                            Yii::$app->getSession()->setFlash('error', $message);
                        }
                        $auth = Source::getSource($user, $this);
                        if ($auth->save()) {
                            $model = new Logon();
                            $model->logon($user, true);
                        } else {
                            $message = 'Не удалось зарегистрировать пользователя. Ошибка: ';
                            $message .= Yii::$app->helpers->getFirstErrorString($auth);
                            Yii::$app->getSession()->setFlash('error', $message);
                        }
                    } else {
                        $message = 'Не удалось зарегистрировать пользователя. Ошибка: ';
                        $message .= Yii::$app->helpers->getFirstErrorString($user);
                        Yii::$app->getSession()->setFlash('error', $message);
                    }
                }
            }
        } else { // Пользователь уже зарегистрирован
            $user = Yii::$app->helpers->checkAuthorization();
            $auth = Source::getSource($user, $this);
            if (!$auth->save()) {
                $message = 'Ошибка: ';
                $message .= Yii::$app->helpers->getFirstErrorString($auth);
                Yii::$app->getSession()->setFlash('error', $message);
            }
        }
    }
}
