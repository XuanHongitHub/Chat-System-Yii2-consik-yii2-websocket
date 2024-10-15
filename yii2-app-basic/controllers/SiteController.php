<?php

namespace app\controllers;

use app\models\Contacts;
use app\models\Messages;
use app\models\User;

use app\models\ResendVerificationEmailForm;
use app\models\VerifyEmailForm;
use Yii;
use yii\base\InvalidArgumentException;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use app\models\LoginForm;
use app\models\PasswordResetRequestForm;
use app\models\ResetPasswordForm;
use app\models\SignupForm;
use app\models\ContactForm;
use app\models\ChatRooms;
use app\models\ChatRoomUser;
use yii\data\ActiveDataProvider;

/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout', 'signup'],
                'rules' => [
                    [
                        'actions' => ['signup'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => \yii\web\ErrorAction::class,
            ],
            'captcha' => [
                'class' => \yii\captcha\CaptchaAction::class,
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        $currentUserId = Yii::$app->user->id;

        // Lấy tất cả các contact của user hiện tại
        $contacts = Contacts::find()
            ->with('user')
            ->where(['user_id' => $currentUserId])
            ->all();

        $activeContactId = null;
        $chatRooms = ChatRooms::find()
            ->innerJoin('chat_room_user', 'chat_rooms.id = chat_room_user.chat_room_id')
            ->where(['chat_room_user.user_id' => $currentUserId])
            ->all();

        $contactData = [];
        foreach ($contacts as $contact) {
            $user = User::findOne($contact->contact_user_id);
            $avatarUrl = !empty($user->avatar) ? $user->avatar : 'https://icons.veryicon.com/png/o/miscellaneous/common-icons-30/my-selected-5.png';

            // Lấy tin nhắn cuối cùng cho từng contact
            $lastMessage = Messages::find()
                ->where([
                    'or',
                    ['user_id' => $currentUserId, 'recipient_id' => $contact->contact_user_id],
                    ['user_id' => $contact->contact_user_id, 'recipient_id' => $currentUserId]
                ])
                ->orderBy(['created_at' => SORT_DESC])
                ->limit(1)
                ->one();

            $lastMessageContent = $lastMessage ? $lastMessage->content : 'No messages';
            $relativeTime = $lastMessage ? Yii::$app->formatter->asRelativeTime($lastMessage->created_at) : '';
            $related = Contacts::find()
                ->where(['user_id' => $contact->contact_user_id])
                ->one();
            $relatedId = $related->id;
            $contactData[] = [
                'id' => $contact->id,
                'avatarUrl' => $avatarUrl,
                'username' => $contact->contactUser->username,
                'lastMessageContent' => $lastMessageContent,
                'relativeTime' => $relativeTime,
                'recipientId' => $contact->contact_user_id,
                'relatedId' => $relatedId,
            ];
        }

        $roomData = [];
        foreach ($chatRooms as $room) {
            $avatarUrl = 'https://i0.wp.com/bane-tech.com/wp-content/uploads/2015/10/R.png?ssl=1';

            $members = ChatRoomUser::find()
                ->where(['chat_room_id' => $room->id])
                ->joinWith('user')
                ->all();

            $avatars = [];
            foreach ($members as $member) {
                $user = $member->user;
                $avatarUrl = !empty($user->avatar) ? $user->avatar : 'https://i0.wp.com/bane-tech.com/wp-content/uploads/2015/10/R.png?ssl=1';
                $avatars[] = $avatarUrl;
            }

            // Lấy tin nhắn cuối cùng cho từng phòng chat
            $lastMessage = Messages::find()
                ->where(['chat_room_id' => $room->id])
                ->orderBy(['created_at' => SORT_DESC])
                ->limit(1)
                ->one();

            $lastMessageContent = $lastMessage ? $lastMessage->content : 'No messages';
            $relativeTime = $lastMessage ? Yii::$app->formatter->asRelativeTime($lastMessage->created_at) : '';

            $roomData[] = [
                'id' => $room->id,
                'name' => $room->name,
                'avatars' => $avatars,
                'lastMessageContent' => $lastMessageContent,
                'relativeTime' => $relativeTime,
            ];
        }
        return $this->render('index', [
            'contacts' => $contactData,
            'roomData' => $roomData,
            'activeContactId' => $activeContactId,

        ]);
    }

    public function actionChat()
    {
        return $this->render('chat');
    }
    /**
     * Logs in a user.
     *
     * @return mixed
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';

        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logs out the current user.
     *
     * @return mixed
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return mixed
     */
    // public function actionContact()
    // {
    //     $model = new ContactForm();
    //     if ($model->load(Yii::$app->request->post()) && $model->validate()) {
    //         if ($model->sendEmail(Yii::$app->params['adminEmail'])) {
    //             Yii::$app->session->setFlash('success', 'Thank you for contacting us. We will respond to you as soon as possible.');
    //         } else {
    //             Yii::$app->session->setFlash('error', 'There was an error sending your message.');
    //         }

    //         return $this->refresh();
    //     }

    //     return $this->render('contact', [
    //         'model' => $model,
    //     ]);
    // }

    /**
     * Displays about page.
     *
     * @return mixed
     */
    public function actionAbout()
    {
        return $this->render('about');
    }

    /**
     * Signs user up.
     *
     * @return mixed
     */
    public function actionSignup()
    {
        $model = new SignupForm();
        if ($model->load(Yii::$app->request->post()) && $model->signup()) {
            Yii::$app->session->setFlash('success', 'Thank you for registration. Please check your inbox for verification email.');
            return $this->goHome();
        }

        return $this->render('signup', [
            'model' => $model,
        ]);
    }

    /**
     * Requests password reset.
     *
     * @return mixed
     */
    // public function actionRequestPasswordReset()
    // {
    //     $model = new PasswordResetRequestForm();
    //     if ($model->load(Yii::$app->request->post()) && $model->validate()) {
    //         if ($model->sendEmail()) {
    //             Yii::$app->session->setFlash('success', 'Check your email for further instructions.');

    //             return $this->goHome();
    //         }

    //         Yii::$app->session->setFlash('error', 'Sorry, we are unable to reset password for the provided email address.');
    //     }

    //     return $this->render('requestPasswordResetToken', [
    //         'model' => $model,
    //     ]);
    // }

    /**
     * Resets password.
     *
     * @param string $token
     * @return mixed
     * @throws BadRequestHttpException
     */
    // public function actionResetPassword($token)
    // {
    //     try {
    //         $model = new ResetPasswordForm($token);
    //     } catch (InvalidArgumentException $e) {
    //         throw new BadRequestHttpException($e->getMessage());
    //     }

    //     if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
    //         Yii::$app->session->setFlash('success', 'New password saved.');

    //         return $this->goHome();
    //     }

    //     return $this->render('resetPassword', [
    //         'model' => $model,
    //     ]);
    // }

    /**
     * Verify email address
     *
     * @param string $token
     * @throws BadRequestHttpException
     * @return yii\web\Response
     */
    // public function actionVerifyEmail($token)
    // {
    //     try {
    //         $model = new VerifyEmailForm($token);
    //     } catch (InvalidArgumentException $e) {
    //         throw new BadRequestHttpException($e->getMessage());
    //     }
    //     if (($user = $model->verifyEmail()) && Yii::$app->user->login($user)) {
    //         Yii::$app->session->setFlash('success', 'Your email has been confirmed!');
    //         return $this->goHome();
    //     }

    //     Yii::$app->session->setFlash('error', 'Sorry, we are unable to verify your account with provided token.');
    //     return $this->goHome();
    // }

    /**
     * Resend verification email
     *
     * @return mixed
     */
    // public function actionResendVerificationEmail()
    // {
    //     $model = new ResendVerificationEmailForm();
    //     if ($model->load(Yii::$app->request->post()) && $model->validate()) {
    //         if ($model->sendEmail()) {
    //             Yii::$app->session->setFlash('success', 'Check your email for further instructions.');
    //             return $this->goHome();
    //         }
    //         Yii::$app->session->setFlash('error', 'Sorry, we are unable to resend verification email for the provided email address.');
    //     }

    //     return $this->render('resendVerificationEmail', [
    //         'model' => $model
    //     ]);
    // }
}