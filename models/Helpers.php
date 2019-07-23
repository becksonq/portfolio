<?php
/**
 * File: Helpers.php
 * Email: becksonq@gmail.com
 * Date: 27.10.2017
 * Time: 21:27
 */

namespace common\models;

use phpDocumentor\Reflection\Types\Object_;
use Yii;
use yii\helpers\HtmlPurifier;

class Helpers
{
    const META_ID_NUM_MIN = 10;
    const META_ID_NUM_MAX = 1000;

    /**
     * Функция для перевода типа объявления
     *
     * @param $arg
     * @return string
     */
    public function convertType($arg)
    {
        $type = '';

        switch ($arg) {
            case 1:
                $type = 'Продам';
                break;
            case 2:
                $type = 'Сдам';
                break;
            case 3:
                $type = 'Сниму';
                break;
            case 4:
                $type = 'Предлагаю';
                break;
            case 5:
                $type = 'Воспользуюсь';
                break;
            case 6:
                $type = 'Ищу';
                break;
            case 7:
                $type = 'Отдам';
                break;
            case 8:
                $type = 'Приму в дар';
                break;
            case 9:
                $type = 'Обменяю';
                break;
        }

        return $type;
    }

    /**
     * Функция для перевода ip в integer
     * @param $ip
     * @return int|number
     */
    public static function IpToNum($ip)
    {
        return ip2long($ip);
    }

    /**
     * @param $ip
     * @return int|number
     */
    public static function IpToNumOld($ip)
    {
        if ($ip == "") {
            return 0;
        }
        $num = explode(".", $ip);
        return hexdec(sprintf("%02x%02x%02x%02x", $num[0], $num[1], $num[2], $num[3]));
    }

    /**
     * Функция для перевода integer в ip
     * @param $num
     * @return string
     */
    public static function NumToIp($num)
    {
        return long2ip($num);
    }

    /**
     * @param $num
     * @return string
     */
    public static function NumToIpOld($num)
    {
        $ip = $num + 0.0;
        return sprintf("%d.%d.%d.%d", ($ip >> 24 & 0xFF), ($ip >> 16 & 0xFF),
            ($ip >> 8 & 0xFF), ($ip & 0xFF));
    }

    /**
     * @param $string
     * @param $length
     * @return string
     */
    public static function getShortComment($string, $length)
    {
        $s = HtmlPurifier::process(html_entity_decode($string));

        Utf8::strlen($s) > $length ? $result = Utf8::substr($s, 0, $length) : $result = $s;

        return $result . '...';
    }

    /**
     * @param $price
     * @return string
     */
    public static function format($price)
    {
        return number_format($price, 0, '.', ' ');
    }

    /**
     * @return string
     */
    public static function generateAdvertMetaId()
    {
        // TODO: проверить методы
        // Yii::$app->security->generateRandomString();
        // Yii::$app->security->generateRandomKey($length);
        return uniqid(rand(self::META_ID_NUM_MIN, self::META_ID_NUM_MAX), false);
    }

    /**
     * @return int|string
     * @throws \yii\base\InvalidConfigException
     */
    public static function countPerMonth($param)
    {
        $today = Yii::$app->formatter->asDate('now', 'yyyy-MM-dd');
        /** @var object $param */
        $count = $param::find()
            ->select(['id'])
            ->where([
                'between',
                'created_at',
                (strtotime(date("Y-m-d", strtotime("-1 month")))),
                strtotime($today . ' 23:59:59')
            ])
            ->count();

        return $count;
    }

    /**
     * @return int|string
     * @throws \yii\base\InvalidConfigException
     */
    public static function countPerDay($param)
    {
        $today = Yii::$app->formatter->asDate('now', 'yyyy-MM-dd');
        /** @var object $param */
        $count = $param::find()
            ->select(['id'])
            ->where(['between', 'created_at', strtotime($today . ' 00:00:00'), strtotime($today . ' 23:59:59')])
            ->count();

        return $count;
    }

    /**
     * @param $id
     * @return mixed|null
     */
    public static function prevPageBtn($model, $id)
    {
        /** @var $model object */
        $target = $model::find()->where(['<', 'id', $id])->andWhere([
            'status' => Yii::$app->params['active'],
        ])->orderBy('id DESC')->one();
        if ($target !== null) {
            return $target->id;
        }
        return null;
    }

    /**
     * @param $id
     * @return mixed|null
     */
    public static function nextPageBtn($model, $id)
    {
        /** @var $model object */
        $target = $model::find()->where(['>', 'id', $id])->andWhere([
            'status' => Yii::$app->params['active'],
        ])->orderBy('id ASC')->one();
        if ($target !== null) {
            return $target->id;
        }
        return null;
    }

    /**
     * @param $email
     * @return string
     */
    public static function protectEmail($email)
    {
        $result = "";
        for ($i = 0; $i < strlen($email); $i++) {
            $result .= "&#" . ord(substr($email, $i, 1)) . ";";
        }
        return $result;
    }

    /**
     * @param string $param
     * @return string
     */
    public static function pageString($param = 'page'): string
    {
        $page = (int)Yii::$app->request->get($param, 1);
        return $page > 1 ? ' - Страница ' . $page : '';
    }

}