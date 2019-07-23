<?php
/**
 * File: SubmenuTabs.php
 * Email: becksonq@gmail.com
 * Date: 02.12.2017
 * Time: 9:19
 */

namespace common\widgets\submenu;

use yii;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Url;
use common\models\Subcategories;

class SubmenuTabs extends Widget
{
    public function init()
    {
        parent::init();
    }

    /**
     * @return string
     */
    public function run()
    {
        parent::run();

        return $this->render('index', [
            'items' => $this->_getSubItems($this->_getSubcategory(Yii::$app->request->get('id')))
        ]);
    }

    /**
     * @param $array
     * @return array
     */
    private function _getSubItems($array)
    {
        $extra_items = [];

        foreach ($array as $value) {
            array_push($extra_items, [
                'label' => Html::encode($value->subcat_name),
                'url'   => Url::to([
                    'adverts/subcategory',
                    'id'     => $value->id,
                    'catid'  => Yii::$app->request->get('id'),
                    'cat'    => Yii::$app->request->get('cat'),
                    'subcat' => $value->slug
                ])
            ]);
        }
        unset ($value);

        return $extra_items;
    }

    /**
     * @param $id
     * @return array|null|yii\db\ActiveRecord[]
     */
    private function _getSubcategory($id)
    {
        $subcategory = Subcategories::find()
            ->where(['cat_id' => $id])
            ->orderBy('sort')
            ->all();

        if (!$subcategory) {
            return null;
        }

        return $subcategory;
    }
}