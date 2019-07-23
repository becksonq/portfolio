<?php

namespace common\models;

use common\behaviors\ImageBehavior;
use Yii;
use dektrium\user\models\User;
use yii\db\ActiveRecord;
use common\modules\imageuploads\models\Images;

/**
 * This is the model class for table "{{%adverts}}".
 *
 * @property int $id
 * @property int $cat_id
 * @property int $subcat_id
 * @property int $type_id
 * @property string $header
 * @property string $description
 * @property int $country_id
 * @property int $period_id
 * @property string $createdBy
 * @property int $user_id
 * @property string $email
 * @property int $selected
 * @property int $special
 * @property int $ip
 * @property int $created_at
 * @property int $updated_at
 * @property int $status
 * @property string $meta_id
 * @property int $has_images
 * @property int $views
 * @property int $response_id
 * @property int old_id
 * @property int lift_up
 * @property int lift_up_date
 *
 * @property Categories $cat
 * @property Countries $country
 * @property Periods $period
 * @property Subcategories $subcat
 * @property Types $type
 * @property User $user
 * @property AdvPhones $phones
 * @property Responses $responses
 * @property Images $images
 */
class Adverts extends \yii\db\ActiveRecord
{
    public const STATUS_DRAFT     = 0;
    public const STATUS_PUBLISHED = 1;
    public const STATUS_ACTIVE    = 2;
    public const STATUS_INACTIVE  = 3;
    public const STATUS_DELETED   = 4;

    public const LIFTED_UP = 1;
    public const LIFT_UP_TIME = 3600;

    /* Количество страниц по умолчанию */
    const DEFAULT_PAGE_SIZE = 25;
    const PAGE_SIZE_LIMIT_MIN = 15;
    const PAGE_SIZE_LIMIT_MAX = 100;

    /** Вывод объявлений */
    public const MODE_COOKIE = 'mode';
    public const MODE_LIST = '0';
    public const MODE_IMG = '1';

    public const HAS_IMAGES = 1;
    public const HAS_NOT_IMAGES = 0;

    public const META_ID_NUM_MIN = 10;
    public const META_ID_NUM_MAX = 1000;

    public const SCENARIO_GUEST = 'guest';
    public const SCENARIO_SAVE = 'save';
    public const SCENARIO_CONSOLE = 'console';

    public $verifyCode;

    public $reCaptcha;

    /**
     * Adverts constructor.
     * @param bool $meta_id
     * @param bool $status
     * @param array $config
     */
    public function __construct($meta_id = null, $ip = null, $status = null, array $config = [])
    {
        parent::__construct($config);

        $meta_id === null ? $this->meta_id = Helpers::generateAdvertMetaId() : $this->meta_id = $meta_id;
        $ip === null ? $this->getUserIp() : $this->ip = $ip;
        $status === null ? $this->status = self::STATUS_DRAFT : $this->status = $status;
    }

    /**
     * @return int|number
     */
    protected function getUserIp()
    {
        if (Yii::$app->id === 'app-console') {
            return $this->ip = Helpers::IpToNum('127.0.0.1');
        }

        return $this->ip = Helpers::IpToNum(Yii::$app->request->userIP);
    }
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%adverts}}';
    }

    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => 'yii\behaviors\TimestampBehavior',
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
            ],
            'imageBehavior' => [
                'class' => ImageBehavior::className(),
                'imagePath' => Images::getUploadPath(),
            ],
        ];
    }

    /** @inheritdoc */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_SAVE] = ['status',];
        $scenarios[self::SCENARIO_CONSOLE] = ['old_id'];
        return $scenarios;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['old_id', 'cat_id', 'subcat_id', 'type_id', 'country_id', 'period_id', 'user_id', 'selected', 'special', 'ip', 'created_at', 'updated_at', 'status', 'has_images', 'views', 'response_id', 'lift_up',], 'integer'],
            [['cat_id', 'subcat_id', 'type_id', 'header', 'description', 'country_id', 'period_id', 'createdBy', 'email', 'ip', 'meta_id'], 'required'],
            [['description', 'meta_id',], 'string'],
            [['header', 'createdBy', 'email'], 'string', 'max' => 255],
            ['lift_up_date', 'date', 'format' => 'php:Y-m-d H:i:s'],
            [['cat_id'], 'exist', 'skipOnError' => true, 'targetClass' => Categories::className(), 'targetAttribute' => ['cat_id' => 'id']],
            [['country_id'], 'exist', 'skipOnError' => true, 'targetClass' => Countries::className(), 'targetAttribute' => ['country_id' => 'id']],
            [['period_id'], 'exist', 'skipOnError' => true, 'targetClass' => Periods::className(), 'targetAttribute' => ['period_id' => 'id']],
            [['subcat_id'], 'exist', 'skipOnError' => true, 'targetClass' => Subcategories::className(), 'targetAttribute' => ['subcat_id' => 'id']],
            [['type_id'], 'exist', 'skipOnError' => true, 'targetClass' => Types::className(), 'targetAttribute' => ['type_id' => 'id']],
            [
                ['reCaptcha'],
                \himiklab\yii2\recaptcha\ReCaptchaValidator::className(),
                'message' => 'Подтвердите, что Вы не робот',
                'when'    => function ($model) {
                    return Yii::$app->user->isGuest;
                },
                'except' => ['console'],
            ],
//            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'             => Yii::t('common', 'ID'),
            'old_id'         => Yii::t('common', 'Old ID'),
            'cat_id'         => Yii::t('common', 'Cat ID'),
            'subcat_id'      => Yii::t('common', 'Subcat ID'),
            'type_id'        => Yii::t('common', 'Type ID'),
            'header'         => Yii::t('common', 'Header'),
            'description'    => Yii::t('common', 'Description'),
            'country_id'     => Yii::t('common', 'Country ID'),
            'period_id'      => Yii::t('common', 'Period ID'),
            'createdBy'      => Yii::t('common', 'Created By'),
            'user_id'        => Yii::t('common', 'User ID'),
            'email'          => Yii::t('common', 'Email'),
            'selected'       => Yii::t('common', 'Selected'),
            'special'        => Yii::t('common', 'Special'),
            'ip'             => Yii::t('common', 'Ip'),
            'created_at'     => Yii::t('common', 'Created At'),
            'updated_at'     => Yii::t('common', 'Updated At'),
            'status'         => Yii::t('common', 'status'),
            'verifyCode'     => Yii::t('common', 'Verify Code'),
            'meta_id'        => Yii::t('common', 'Meta ID'),
            'has_images'     => Yii::t('common', 'Has Images'),
            'views'          => Yii::t('common', 'Views'),
            'response_id'    => Yii::t('common', 'Response ID'),
            'lift_up'        => Yii::t('common', 'Lift Up'),
            'lift_up_date'   => Yii::t('common', 'Lift Up Date'),
        ];
    }

    /**
     * @return bool
     */
    public function beforeValidate()
    {
        if ($this->isNewRecord && Yii::$app instanceof \yii\web\Application) {
            empty(Yii::$app->user->identity->id)
                ? $this->user_id = null
                : $this->user_id = Yii::$app->user->identity->id;
            return true;
        }
        return parent::beforeValidate();
    }

    /**
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if (Yii::$app->id !== 'app-console') {
            $this->has_images = $this->_setHasImages($this->meta_id);
        }

        return parent::beforeSave($insert);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCat()
    {
        return $this->hasOne(Categories::className(), ['id' => 'cat_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCountry()
    {
        return $this->hasOne(Countries::className(), ['id' => 'country_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPeriod()
    {
        return $this->hasOne(Periods::className(), ['id' => 'period_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSubcat()
    {
        return $this->hasOne(Subcategories::className(), ['id' => 'subcat_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getType()
    {
        return $this->hasOne(Types::className(), ['id' => 'type_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPrices()
    {
        return $this->hasOne(Prices::class, ['adv_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImages()
    {
        return $this->hasMany(Images::className(), ['ad_meta_id' => 'meta_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPhones()
    {
        return $this->hasMany(AdvPhones::className(), ['ad_meta_id' => 'meta_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getResponses()
    {
        return $this->hasMany(Responses::className(), ['adv_id' => 'id']);
    }

    /**
     * @return int|string
     * @throws \yii\base\InvalidConfigException
     */
    public static function countPerMonth()
    {
        $today = Yii::$app->formatter->asDate('now', 'yyyy-MM-dd');
        $count = self::find()
            ->select(['id'])
            ->where([
                'between',
                'created_at',
                (strtotime(date('Y-m-d', strtotime('-1 month')))),
                strtotime($today . ' 23:59:59')
            ])
            ->count();

        return $count;
    }

    /**
     * @return int|string
     * @throws \yii\base\InvalidConfigException
     */
    public static function countPerDay()
    {
        $today = Yii::$app->formatter->asDate('now', 'yyyy-MM-dd');
        $count = self::find()
            ->select(['id'])
            ->where(['between', 'created_at', strtotime($today . ' 00:00:00'), strtotime($today . ' 23:59:59')])
            ->count();

        return $count;
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
     * @return bool
     */
    public function isDraft()
    {
        return $this->status == self::STATUS_DRAFT;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->status == self::STATUS_ACTIVE;
    }

    /**
     * @return bool
     */
    public function isInactive()
    {
        return $this->status == self::STATUS_INACTIVE;
    }

    /**
     * @return bool
     */
    public function isPublished()
    {
        return $this->status == self::STATUS_PUBLISHED;
    }

    /**
     * @return bool
     */
    public function isDeleted()
    {
        return $this->status == self::STATUS_DELETED;
    }

    /**
     * @param $id
     * @return mixed|null
     */
    public static function prevPageBtn($id)
    {
        $target = Adverts::find()->where(['<', 'id', $id])->orderBy('id DESC')->one();
        if ($target !== null) {
            return $target->id;
        }
        return null;
    }

    /**
     * @param $id
     * @return mixed|null
     */
    public static function nextPageBtn($id)
    {
        $target = Adverts::find()->where(['>', 'id', $id])->orderBy('id ASC')->one();
        if ($target !== null) {
            return $target->id;
        }
        return null;
    }

    /**
     * @param $metaId
     * @return int|null
     */
    private function _setHasImages($metaId)
    {
        $countImages = Images::find()->where(['ad_meta_id' => $metaId])->count();
        return $countImages > 0
            ? Adverts::HAS_IMAGES
            : Adverts::HAS_NOT_IMAGES;
    }

    /**
     * @return bool
     */
    public function isLifted(): bool
    {
        return $this->lift_up == self::LIFTED_UP;
    }

    /**
     * @return bool
     */
    public function isHasImages(): bool
    {
        return $this->has_images == self::HAS_IMAGES;
    }

    /**
     * @return array|mixed|string
     */
    public static function typeView()
    {
        $mode = yii::$app->request->get(self::MODE_COOKIE);
        if (isset($mode) && $mode != '') {
            $typeView = $mode;
        } else {
            $cookies = Yii::$app->request->cookies;
            if (($cookie = $cookies->get(self::MODE_COOKIE)) !== null) {
                $typeView = $cookie->value;
            } else {
                $typeView = self::MODE_LIST;
            }
        }

        return $typeView;
    }
}
