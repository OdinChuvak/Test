<?php

namespace console\controllers;

use common\models\User;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Exception;
use yii\helpers\Console;

class UserController extends Controller
{
    /**
     * Создать нового пользователя
     * @param string $username Логин
     * @param string $email Email
     * @param string $password Пароль
     * @param string $role Роль (необязательно)
     * @return int Exit code
     * @throws Exception
     * @throws \Exception
     */
    public function actionCreate($username, $email, $password, $role = null)
    {
        $user = new User();
        $user->username = $username;
        $user->email = $email;
        $user->setPassword($password);
        $user->generateAuthKey();
        $user->status = User::STATUS_ACTIVE;

        if ($user->save()) {
            $this->stdout("Пользователь успешно создан!\n", Console::FG_GREEN);
            $this->stdout("ID: {$user->id}\n");
            $this->stdout("Имя: {$user->username}\n");
            $this->stdout("Email: {$user->email}\n");

            // Назначение роли, если указана
            if ($role) {
                $auth = \Yii::$app->authManager;
                $userRole = $auth->getRole($role);
                if ($userRole) {
                    $auth->assign($userRole, $user->id);
                    $this->stdout("Роль '{$role}' назначена\n", Console::FG_GREEN);
                } else {
                    $this->stdout("Роль '{$role}' не найдена\n", Console::FG_YELLOW);
                }
            }

            return ExitCode::OK;
        } else {
            $this->stderr("Ошибка при создании пользователя:\n", Console::FG_RED);
            foreach ($user->errors as $errors) {
                foreach ($errors as $error) {
                    $this->stderr("- {$error}\n");
                }
            }
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Список всех пользователей
     * @return int Exit code
     */
    public function actionList()
    {
        $users = User::find()->all();

        if (empty($users)) {
            $this->stdout("Пользователи не найдены\n", Console::FG_YELLOW);
            return ExitCode::OK;
        }

        $this->stdout("Всего пользователей: " . count($users) . "\n\n");

        foreach ($users as $user) {
            $status = $user->status == User::STATUS_ACTIVE ?
                Console::ansiFormat('Активен', [Console::FG_GREEN]) :
                Console::ansiFormat('Неактивен', [Console::FG_RED]);

            $this->stdout("ID: {$user->id}\n");
            $this->stdout("Имя: {$user->username}\n");
            $this->stdout("Email: {$user->email}\n");
            $this->stdout("Статус: {$status}\n");
            $this->stdout("Создан: " . date('d.m.Y H:i', $user->created_at) . "\n");
            $this->stdout("Обновлен: " . date('d.m.Y H:i', $user->updated_at) . "\n");
            $this->stdout(str_repeat("-", 40) . "\n");
        }

        return ExitCode::OK;
    }

    /**
     * Изменить пароль пользователя
     * @param string $username Логин или email
     * @param string $newPassword Новый пароль
     * @return int Exit code
     * @throws Exception
     */
    public function actionChangePassword($username, $newPassword)
    {
        $user = User::find()->where(['or',
            ['username' => $username],
            ['email' => $username]
        ])->one();

        if (!$user) {
            $this->stderr("Пользователь не найден\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $user->setPassword($newPassword);

        if ($user->save()) {
            $this->stdout("Пароль успешно изменен для пользователя: {$user->username}\n", Console::FG_GREEN);
            return ExitCode::OK;
        } else {
            $this->stderr("Ошибка при изменении пароля:\n", Console::FG_RED);
            foreach ($user->errors as $errors) {
                foreach ($errors as $error) {
                    $this->stderr("- {$error}\n");
                }
            }
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Активировать/деактивировать пользователя
     * @param string $username Логин или email
     * @param string $status Статус (active/inactive)
     * @return int Exit code
     * @throws Exception
     */
    public function actionSetStatus($username, $status = 'active')
    {
        $user = User::find()->where(['or',
            ['username' => $username],
            ['email' => $username]
        ])->one();

        if (!$user) {
            $this->stderr("Пользователь не найден\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $user->status = $status === 'active' ? User::STATUS_ACTIVE : User::STATUS_INACTIVE;

        if ($user->save()) {
            $statusText = $user->status == User::STATUS_ACTIVE ? 'активирован' : 'деактивирован';
            $this->stdout("Пользователь {$user->username} успешно {$statusText}\n", Console::FG_GREEN);
            return ExitCode::OK;
        } else {
            $this->stderr("Ошибка при изменении статуса\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Удалить пользователя
     * @param string $username Логин или email
     * @param bool $force Принудительное удаление
     * @return int Exit code
     */
    public function actionDelete($username, $force = false)
    {
        $user = User::find()->where(['or',
            ['username' => $username],
            ['email' => $username]
        ])->one();

        if (!$user) {
            $this->stderr("Пользователь не найден\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (!$force) {
            $confirm = Console::confirm("Вы уверены, что хотите удалить пользователя '{$user->username}'?");
            if (!$confirm) {
                $this->stdout("Удаление отменено\n", Console::FG_YELLOW);
                return ExitCode::OK;
            }
        }

        if ($user->delete()) {
            $this->stdout("Пользователь успешно удален\n", Console::FG_GREEN);
            return ExitCode::OK;
        } else {
            $this->stderr("Ошибка при удалении пользователя\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }
}