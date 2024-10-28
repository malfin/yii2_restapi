<?php
/**
 * @author aleksejpuhov
 * File: UserController.php
 * Date: 27.10.2024
 * Time: 10:14
 */

namespace app\controllers;

use app\models\User;
use Yii;
use yii\db\Exception;
use yii\filters\VerbFilter;
use yii\rest\Controller;
use yii\web\Response;

class UserController extends Controller
{

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats'] = [
            'application/json' => Response::FORMAT_JSON,
        ];
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'register' => ['POST'],
                'token' => ['POST'],
                'logout' => ['GET']
            ],
        ];
        return $behaviors;
    }

    protected function validationErrorResponse($errors)
    {
        Yii::$app->response->statusCode = 422;
        return [
            'status' => false,
            'message' => $errors,
        ];
    }

    /**
     * @throws Exception
     */
    public function actionToken()
    {
        $request = Yii::$app->request->post();
        $user = User::findOne(['username' => $request['username']]);
        if ($user && $user->validatePassword($request['password'])) {
            $user->generateToken();
            if ($user->save()) {
                return [
                    'status' => true,
                    'user_id' => $user->id,
                    'token' => $user->token,
                ];
            }
        }
        return $this->validationErrorResponse($user->errors);
    }


    /**
     * @throws Exception
     */
    public function actionRegister(): array
    {
        $user = new User();
        $user->username = Yii::$app->request->post('username');
        $user->first_name = Yii::$app->request->post('first_name');
        $user->last_name = Yii::$app->request->post('last_name');
        $user->email = Yii::$app->request->post('email');
        $user->HashPassword(Yii::$app->request->post('password'));
        $user->generateToken();
        if ($user->validate() && $user->save()) {
            return [
                'status' => true,
                'user_id' => $user->id,
                'token' => $user->token,
            ];
        }
        return $this->validationErrorResponse($user->errors);
    }

    /**
     * @throws Exception
     */
    public function actionLogout(): array
    {
        // Получаем токен из заголовка
        $authorizationHeader = Yii::$app->request->headers->get('Authorization');

        if (empty($authorizationHeader)) {
            return $this->authenticationErrorResponse(); // Возвращаем ошибку 403
        }

        // Извлечение токена
        $token = str_replace('Bearer ', '', $authorizationHeader);

        // Поиск пользователя по токену
        $user = User::findOne(['token' => $token]);

        if (!$user) {
            return [
                'status' => false,
                'message' => 'Пользователь не найден.',
            ];
        }

        // Удаляем токен у пользователя и сохраняем
        $user->token = null;
        return $user->save() ?
            ['status' => true, 'message' => 'Вы успешно вышли из системы.'] :
            ['status' => false, 'message' => 'Ошибка при выходе из системы.'];
    }

}