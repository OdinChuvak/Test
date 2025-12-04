<?php

namespace backend\controllers;

use common\models\Apple;
use common\models\LoginForm;
use Yii;
use yii\db\Exception;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['login', 'error'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout', 'index', 'generate-apples', 'fall-apple', 'eat-apple', 'delete-apple'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                    'generate-apples' => ['post'],
                    'fall-apple' => ['post'],
                    'eat-apple' => ['post'],
                    'delete-apple' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions(): array
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    /**
     * Displays homepage with apples.
     *
     * @return string
     */
    public function actionIndex(): string
    {
        // Получаем все яблоки с сортировкой по статусу и дате
        $apples = Apple::find()
            ->orderBy([
                'status' => SORT_ASC,
                'appearance_date' => SORT_DESC
            ])
            ->all();

        // Проверяем гниение всех упавших яблок
        Apple::checkAllForRot();

        return $this->render('index', [
            'apples' => $apples,
        ]);
    }

    /**
     * Генерация случайных яблок
     * @return Response
     * @throws Exception
     */
    public function actionGenerateApples(): Response
    {
        $count = rand(1, 10); // Генерируем от 1 до 10 яблок
        $created = Apple::createRandomBatch($count);

        Yii::$app->session->setFlash(
            $created > 0 ? 'success' : 'warning',
            $created > 0
                ? "Сгенерировано {$created} случайных яблок!"
                : "Не удалось сгенерировать яблоки"
        );

        return $this->redirect(['index']);
    }

    /**
     * Уронить яблоко
     * @param int $id ID яблока
     * @return Response
     */
    public function actionFallApple($id): Response
    {
        $apple = Apple::findOne($id);

        if (!$apple) {
            Yii::$app->session->setFlash('error', 'Яблоко не найдено!');
            return $this->redirect(['index']);
        }

        if ($apple->fall()) {
            Yii::$app->session->setFlash('success', "Яблоко #{$id} упало!");
        } else {
            $errorMessage = implode(', ', $apple->getFirstErrors());
            Yii::$app->session->setFlash('error', "Не удалось уронить яблоко: {$errorMessage}");
        }

        return $this->redirect(['index']);
    }

    /**
     * Съесть яблоко
     * @param int $id ID яблока
     * @return array
     */
    public function actionEatApple(int $id): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $apple = Apple::findOne($id);

        if (!$apple) {
            return [
                'success' => false,
                'message' => 'Яблоко не найдено!'
            ];
        }

        $percent = (float) Yii::$app->request->post('percent', 0);

        if ($percent <= 0 || $percent > 100) {
            return [
                'success' => false,
                'message' => 'Процент должен быть от 1 до 100!'
            ];
        }

        if ($apple->eat($percent)) {
            if ($apple->isNewRecord) {
                return [
                    'success' => true,
                    'message' => "Яблоко полностью съедено!",
                    'redirect' => true
                ];
            } else {
                return [
                    'success' => true,
                    'message' => "Съедено {$percent}% яблока. Осталось: {$apple->size}%",
                    'size' => $apple->size,
                    'eaten_percent' => $apple->eaten_percent
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => 'Не удалось съесть яблоко: ' . implode(', ', $apple->getFirstErrors())
            ];
        }
    }

    /**
     * Удалить яблоко
     * @param int $id ID яблока
     * @return Response
     */
    public function actionDeleteApple(int $id): Response
    {
        $apple = Apple::findOne($id);

        if (!$apple) {
            Yii::$app->session->setFlash('error', 'Яблоко не найдено!');
            return $this->redirect(['index']);
        }

        if ($apple->canDelete()) {
            $apple->delete();
            Yii::$app->session->setFlash('success', "Яблоко #{$id} удалено!");
        } else {
            Yii::$app->session->setFlash('error', "Нельзя удалить яблоко, которое еще не съедено полностью или не сгнило!");
        }

        return $this->redirect(['index']);
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        } else {
            $model->password = '';

            return $this->render('login', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout(): Response
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }
}