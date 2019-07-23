<?php
use yii\bootstrap\Nav;

/** @var $items common\widgets\catmenu\CatmenuTabs */
echo Nav::widget( [
    'items'        => $items,
    'encodeLabels' => false,
    'options'      => [
        'class' => 'nav-tabs'
    ]
] );