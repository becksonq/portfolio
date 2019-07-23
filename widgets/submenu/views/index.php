<?php
use yii\bootstrap\Nav;

/** @var $items \common\widgets\submenu\SubmenuTabs */
echo Nav::widget( [
    'items'   => $items,
    'options' => [
        'class' => 'nav-pills'
    ]
] );