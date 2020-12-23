<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UserController;
use App\Http\Controllers\NationalRankingController;
use App\Http\Controllers\IndividualQuizPlayerController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\IndividualQuizTypeController;
use App\Http\Controllers\IndividualQuizController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\SpecialQuizController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\AnswerController;
use App\Http\Controllers\SeasonController;
use App\Http\Controllers\LeagueController;
use App\Http\Controllers\CupController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\DateController;
use App\Http\Controllers\QuestionsTranslationsController;
use App\Http\Controllers\ExternalQuestionController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\InvitationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('session', [UserController::class, 'login']);

Route::post('password-reset', [UserController::class, 'passwordResetRequest']);
Route::patch('password-reset', [UserController::class, 'passwordResetConfirm']);

Route::post('users', [UserController::class, 'register']);

Route::get('national-rankings', [NationalRankingController::class, 'get']);
Route::get('individual-quiz-players', [IndividualQuizPlayerController::class, 'get']);
Route::get('individual-quizzes', [IndividualQuizController::class, 'get']);
Route::get('users', [UserController::class, 'get']);

Route::get('genres', [GenreController::class, 'get']);
Route::get('regions', [RegionController::class, 'get']);

Route::group([
    'middleware' => 'auth:api',
], function () {
    Route::patch('session', [UserController::class, 'renew']);
    Route::delete('session', [UserController::class, 'logout']);
    Route::patch('users', [UserController::class, 'update']);

    Route::group([
        'middleware' => 'blocked',
    ], function () {
        Route::get('roles', [RoleController::class, 'get']);

        Route::get('permissions', [PermissionController::class, 'get']);

        Route::get('individual-quiz-types', [IndividualQuizTypeController::class, 'get']);

        Route::post('individual-quiz-players', [IndividualQuizPlayerController::class, 'create']);
        Route::patch('individual-quiz-players', [IndividualQuizPlayerController::class, 'update']);
        Route::delete('individual-quiz-players', [IndividualQuizPlayerController::class, 'delete']);

        Route::post('individual-quizzes', [IndividualQuizController::class, 'create']);
        Route::patch('individual-quizzes', [IndividualQuizController::class, 'update']);
        Route::delete('individual-quizzes', [IndividualQuizController::class, 'delete']);

        Route::post('national-rankings', [NationalRankingController::class, 'create']);
        Route::delete('national-rankings', [NationalRankingController::class, 'delete']);

        Route::get('notifications', [NotificationController::class, 'get']);
        Route::post('notifications', [NotificationController::class, 'create']);
        Route::patch('notifications', [NotificationController::class, 'update']);
        Route::delete('notifications', [NotificationController::class, 'delete']);

        Route::get('quizzes', [QuizController::class, 'get']);
        Route::post('quizzes', [QuizController::class, 'create']);
        Route::patch('quizzes', [QuizController::class, 'update']);
        Route::delete('quizzes', [QuizController::class, 'delete']);
        Route::post('quizzes/submit', [QuizController::class, 'submit']);

        Route::get('special-quizzes', [SpecialQuizController::class, 'get']);
        Route::post('special-quizzes', [SpecialQuizController::class, 'create']);
        Route::patch('special-quizzes', [SpecialQuizController::class, 'update']);
        Route::delete('special-quizzes', [SpecialQuizController::class, 'delete']);
        Route::post('special-quizzes/submit', [SpecialQuizController::class, 'submit']);

        Route::get('media', [MediaController::class, 'get']);
        Route::post('media', [MediaController::class, 'create']);
        Route::delete('media', [MediaController::class, 'delete']);

        Route::get('answers', [AnswerController::class, 'get']);
        Route::post('answers', [AnswerController::class, 'create']);
        Route::patch('answers', [AnswerController::class, 'update']);

        Route::get('seasons', [SeasonController::class, 'get']);
        Route::post('seasons', [SeasonController::class, 'create']);
        Route::patch('seasons', [SeasonController::class, 'update']);
        Route::delete('seasons', [SeasonController::class, 'delete']);

        Route::get('leagues', [LeagueController::class, 'get']);
        
        Route::get('cups', [CupController::class, 'get']);
        Route::post('cups', [CupController::class, 'create']);
        Route::patch('cups', [CupController::class, 'update']);
        Route::delete('cups', [CupController::class, 'delete']);

        Route::get('games', [GameController::class, 'get']);

        Route::get('questions', [QuestionController::class, 'get']);

        Route::get('logs', [LogController::class, 'get']);
        Route::post('logs', [LogController::class, 'create']);

        Route::post('messages', [MessageController::class, 'send']);

        Route::get('date', [DateController::class, 'get']);

        Route::get('questions-translations', [QuestionsTranslationsController::class, 'get']);
        Route::post('questions-translations', [QuestionsTranslationsController::class, 'create']);
        Route::patch('questions-translations', [QuestionsTranslationsController::class, 'update']);

        Route::get('external-questions', [ExternalQuestionController::class, 'get']);
        Route::patch('external-questions', [ExternalQuestionController::class, 'update']);

        Route::post('payment/create', [PaymentController::class, 'create']);
        Route::post('payment/check', [PaymentController::class, 'check']);

        Route::get('invitations', [InvitationController::class, 'get']);
        Route::post('invitations', [InvitationController::class, 'send']);
        Route::patch('invitations', [InvitationController::class, 'resend']);
    });
});
