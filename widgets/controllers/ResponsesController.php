<?php

namespace frontend\controllers;

use Yii;
use common\models\Responses;
use common\models\ResponsesSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * ResponsesController implements the CRUD actions for Responses model.
 */
class ResponsesController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class'   => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                    'create' => ['POST']
                ],
            ],
        ];
    }

    /**
     * Creates a new Responses model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $responses = new Responses();

        if (Yii::$app->request->isPjax) {
            if ($responses->load(Yii::$app->request->post()) && $responses->save()) {
                $responses = new Responses();
                $isSave = true;
            }

            return $this->renderPartial('/adverts/_response-form', compact('responses', 'isSave'));
        }

        return 0;
    }
}
