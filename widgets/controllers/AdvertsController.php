<?php

namespace frontend\controllers;

use common\models\AdvPhones;
use common\models\Categories;
use common\models\Subcategories;
use common\modules\imageuploads\models\Images;
use common\models\Prices;
use Yii;
use common\models\Adverts;
use common\models\AdvertsSearch;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\Responses;

class AdvertsController extends \yii\web\Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => [
                            'index',
                            'details',
                            'category',
                            'subcategory',
                            'create',
                            'preview',
                            'save',
                            'success',
                            'toggle-view',
                            'update',
                        ],
                        'allow'   => true,
                    ],
                    [
                        'actions' => ['index', 'delete'],
                        'allow'   => true,
                        'roles'   => ['@'],
                    ],
                ],
            ],
            'verbs'  => [
                'class'   => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new AdvertsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\db\Exception
     */
    public function actionCreate()
    {
        $this->layout = 'blank';

        $model = new Adverts();
        $prices = new Prices();
        $images = new Images();
        $phonesArray = [new AdvPhones];

        if (!isset($model, $prices, $images, $phonesArray)) {
            throw new NotFoundHttpException("Models was not found.");
        }

        $post = Yii::$app->request->post();

        $validModel = $model->load($post) && $model->validate();
        $validPrices = $prices->load($post) && $prices->validate();

        if (!empty($post)) {
            $phonesArray = AdvPhones::createMultiples($phonesArray);
            $validPhones = AdvPhones::loadMultiple($phonesArray, $post)
                && AdvPhones::validateMultiple($phonesArray);
        }

        if ($validModel && $validPrices && $validPhones) {
            $transaction = \Yii::$app->db->beginTransaction();
            try {

                if (!$model->save()) {
                    throw new \RuntimeException('Saving $model error.');
                }

                if(!AdvPhones::createPhones($model->meta_id, $phonesArray)){
                    throw new \RuntimeException('Saving adv phones error.');
                }

                $prices->link('adv', $model);
                if (!$prices->save()) {
                    throw new \RuntimeException('Saving $prices error.');
                }

                $transaction->commit();
            } catch (\Exception $e) {
                $transaction->rollBack();
                throw $e;
            } catch (\Throwable $e) {
                $transaction->rollBack();
                throw $e;
            }

            return $this->redirect(['preview', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model'       => $model,
            'prices'      => $prices,
            'images'      => $images,
            'phonesArray' => empty($phonesArray) ? [new AdvPhones()] : $phonesArray,
        ]);
    }

    /**
     * @param $id
     * @return string|\yii\web\Response
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\db\Exception
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionUpdate($id)
    {
        $this->layout = 'blank';

        $model = $this->findModel($id);

        if (!Yii::$app->user->can('editOwnModel', ['adverts' => $model])) {
            throw new \yii\web\ForbiddenHttpException(Yii::t('common', 'Editing ads is available only to the author.'));
        }

        $images = new Images();
        $phonesArray = $model->phones;
        $post = Yii::$app->request->post(); //d($post);

        $validModel = $model->load($post) && $model->validate(); //d($model->getErrors());die;
        $validPrices = $model->prices->load($post) && $model->prices->validate();

        if (!empty($post)) {
            $phonesArray = AdvPhones::createMultiples($phonesArray);
            $validPhones = AdvPhones::loadMultiple($phonesArray, $post)
                && AdvPhones::validateMultiple($phonesArray);
        }

        if ($validModel && $validPrices && $validPhones) {
            $transaction = \Yii::$app->db->beginTransaction();
            try {

                if (!$model->save()) {
                    throw new \RuntimeException('Saving $model error.');
                }
                if (!AdvPhones::createPhones($model->meta_id, $phonesArray)) {
                    throw new \RuntimeException('Saving $AdvPhones error.');
                }

                if (!$model->prices->save()) {
                    throw new \RuntimeException('Saving $prices error.');
                }

                $transaction->commit();
            } catch (\Exception $e) {
                $transaction->rollBack();
                throw $e;
            } catch (\Throwable $e) {
                $transaction->rollBack();
                throw $e;
            }

            return $this->redirect(['preview', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model'       => $model,
            'images'      => $images,
            'phonesArray' => empty($phonesArray) ? [new AdvPhones()] : $phonesArray,
        ]);
    }

    /**
     * @return \yii\console\Response|\yii\web\Response
     * @throws NotFoundHttpException
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionDelete()
    {
        $id = Yii::$app->request->post('id');
        $model = $this->findModel($id);
        $response = Yii::$app->response;
        $response->format = \yii\web\Response::FORMAT_JSON;

        $model->status = Adverts::STATUS_DELETED;
        if ($model->save()) {
            $response->data = ['message' => 'success',];
        } else {
            $response->data = ['message' => 'error', 'errorMessage' => $model->getErrors()];
        }

        return $response;
    }

    /**
     * @param $id
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionDetails($id)
    {
        $this->layout = 'blank';
        $model = $this->findModel($id);
        $searchModel = new AdvertsSearch();
        $similarAds = $searchModel->searchSimilar($model);
        $responses = new Responses();

        // Обновление счетчика просмотров
        $model->updateCounters(['views' => 1]);

        return $this->render('details', compact('model', 'similarAds', 'responses'));
    }

    /**
     * @return string
     */
    public function actionCategory()
    {
        $searchModel = new AdvertsSearch();
        $dataProvider = $searchModel->searchCategoryPage(Yii::$app->request->queryParams);

        return $this->render('category-page', [
            'dataProvider' => $dataProvider,
            'categoryName' => static::getCategoryName($dataProvider),
        ]);
    }

    /**
     * @return string
     */
    public function actionSubcategory()
    {
        $searchModel = new AdvertsSearch();
        $dataProvider = $searchModel->searchSubcategoryPage(Yii::$app->request->queryParams);

        $subcatName = empty($dataProvider->getModels())
            ? Subcategories::findOne(Yii::$app->request->get('id'))
            : $dataProvider->getModels()[0]->subcat;

        return $this->render('subcategory-page', [
            'dataProvider' => $dataProvider,
            'categoryName' => static::getCategoryName($dataProvider),
            'subcatName' => $subcatName,
        ]);
    }

    /**
     * @param ActiveDataProvider $dataProvider
     * @return Categories|null
     */
    protected static function getCategoryName(ActiveDataProvider $dataProvider)
    {
        empty($dataProvider->getModels())
            ? $categoryName = Categories::findOne(Yii::$app->request->get('catid'))
            : $categoryName = $dataProvider->getModels()[0]->cat;

        return $categoryName;
    }

    /**
     * @param $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionSave($id)
    {
        $model = $this->findModel($id);
        $model->scenario = Adverts::SCENARIO_SAVE;

        if ($model->isPublished()) {
            return $this->redirect(['/user/settings/adverts']);
        }

        if ($model->isDraft()) {
            $model->status = Adverts::STATUS_PUBLISHED;
            if ($model->save()) {
                // Отправляем письмо об успешном размещении объявления
                Yii::$app->mailer->compose(
                    ['html' => 'addAdvSuccess-html', 'text' => 'addAdvSuccess-text'],
                    ['user' => $model->createdBy, 'id' => $model->id]
                )
                    ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name . ' robot'])
                    ->setTo([$model->email, Yii::$app->params['adminEmail']])
                    ->setSubject(Yii::t('common', 'Adverts published'))
                    ->send();

                return $this->redirect(['success']);
            }
        }

        return $this->redirect(['preview', 'id' => $model->id]);
    }

    /**
     * Finds the Adverts model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Adverts the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Adverts::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
    }

    /**
     * @param $id
     * @return string
     * @throws \yii\base\Exception
     */
    public function actionPreview($id)
    {
        $this->layout = 'blank';
        $model = $this->findModel($id);

        return $this->render('preview', ['model' => $model,]);
    }

    /**
     * @return string
     */
    public function actionSuccess()
    {
        return $this->render('success');
    }
}
