<?php

namespace common\widgets\catmenu;

use yii;
use yii\bootstrap\Widget;
use yii\helpers\Html;
use yii\helpers\Url;
use common\models\Categories;

/**
 * Class CatmenuTabs
 * @package common\widgets\catmenu
 */
class CatmenuTabs extends Widget
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
            'items' => $this->_getItems($this->_getCategory())
        ]);
    }

    /**
     * @return array|null|yii\db\ActiveRecord[]
     */
    private function _getCategory()
    {
        $category = Categories::find()
            ->joinWith([
                'subcategories' => function ($q) {
                    /** @var $q yii\db\ActiveQuery */
                    $q->orderBy('sort');
                }
            ])
            ->orderBy('sort')
            ->all();

        if (!$category) {
            return null;
        }
        return $category;
    }

    /**
     * @param $models
     * @return array
     */
    private function _getItems($models)
    {
        $i = 0;
        $extraItems = [];
        $subCategory = [];
        $items = [];

        foreach ($models as $value) {
            if ($i >= 5) {
                array_push($extraItems, [
                    'label' => $value->icon . $value->category_name,
                    'url'   => Url::to([
                        'adverts/category',
                        'id'  => $value->id,
                        'cat' => $value->slug
                    ])
                ]);
            } else {
                foreach ($value->subcategories as $val) {
                    array_push($subCategory, [
                        'label' => Html::encode($val->subcat_name),
                        'url'   => Url::to([
                            'adverts/subcategory',
                            'catid'  => $value->id,
                            'id'     => $val->id,
                            'cat'    => $value->slug,
                            'subcat' => $val->slug
                        ])
                    ]);
                }
                unset($val);

                array_push($items, [
                    'label' => $value->icon . $value->category_name,
                    'items' => $subCategory
                ]);
                $subCategory = [];
            }

            $i++;
        }
        unset($value);

        array_push($items, ['label' => 'Прочее', 'items' => $extraItems]);

        return $items;
    }
}