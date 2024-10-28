<?php
/**
 * @author aleksejpuhov
 * File: CategoryController.php
 * Date: 27.10.2024
 * Time: 10:44
 */

namespace app\controllers;

use app\models\Category;
use Yii;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\Controller;
use yii\web\Response;

class CategoryController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
        ];
        $behaviors['contentNegotiator']['formats'] = [
            'application/json' => Response::FORMAT_JSON,
        ];
        return $behaviors;
    }

    public function beforeAction($action)
    {
        if (empty(Yii::$app->request->headers->get('Authorization'))) {
            return $this->authenticationErrorResponse();
        }

        return parent::beforeAction($action);
    }

    protected function authenticationErrorResponse()
    {
        Yii::$app->response->statusCode = 403;
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = [
            'status' => false,
            'message' => 'Учетные данные не были предоставлены.',
        ];
        return false;
    }

    protected function validationErrorResponse($errors)
    {
        Yii::$app->response->statusCode = 422;
        return [
            'status' => false,
            'message' => $errors,
        ];
    }

    public function actionCreate()
    {
        $category = new Category();
        $category->name = Yii::$app->request->post('name');

        if ($category->validate() && $category->save()) {
            return [
                'id' => $category->id,
                'name' => $category->name,
            ];
        }

        return $this->validationErrorResponse($category->errors);
    }
}
