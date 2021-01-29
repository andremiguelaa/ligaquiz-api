<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Request;
use Validator;
use Carbon\Carbon;
use App\Http\Controllers\BaseController as BaseController;
use App\Notification;
use App\Quiz;
use App\SpecialQuiz;
use App\Answer;
use App\QuizQuestion;
use App\SpecialQuizQuestion;

class NotificationController extends BaseController
{
    public function get(Request $request)
    {
        $input = $request::all();
        if (
            Auth::user()->hasPermission('notifications_list') &&
            !array_key_exists('current', $input)
        ) {
            $response = (object) [
                'manual' => Notification::orderBy('start_date', 'desc')->get()
            ];
        } else {
            $now = Carbon::now();
            $notifications = Notification::where('start_date', '<', $now)
                ->where('end_date', '>', $now)
                ->orderBy('start_date', 'desc')
                ->get();
            $response = (object) [
                'manual' => $notifications
            ];
            if (Auth::user()->hasPermission('answer_correct')) {
                $notCorrectedAnswers = Answer::where('submitted', 1)->where('corrected', 0)->select('question_id')->get();
                if ($notCorrectedAnswers->count()) {
                    $questionIds = $notCorrectedAnswers->pluck('question_id')->toArray();
                    $quizIds = array_unique(
                        QuizQuestion::whereIn('question_id', $questionIds)
                            ->select('quiz_id')
                            ->get()
                            ->pluck('quiz_id')
                            ->toArray()
                    );
                    $specialQuizIds = array_unique(
                        SpecialQuizQuestion::whereIn('question_id', $questionIds)
                            ->select('special_quiz_id')
                            ->get()
                            ->pluck('special_quiz_id')
                            ->toArray()
                    );
                    if(count($quizIds)){
                        $response->not_corrected_quizzes = Quiz::whereIn('id', $quizIds)
                            ->select('date')
                            ->get()
                            ->pluck('date')
                            ->toArray();
                    }
                    if(count($specialQuizIds)){
                        $response->not_corrected_special_quizzes =
                            SpecialQuiz::whereIn('id', $specialQuizIds)
                                ->select('date')
                                ->get()
                                ->pluck('date')
                                ->toArray();
                    }
                }
            }
            if (Auth::user()->hasPermission('quiz_play')) {
                $quiz = Quiz::where('date', $now->format('Y-m-d'))->first();
                if ($quiz) {
                    $response->quiz = !$quiz->isSubmitted();
                } else {
                    $response->quiz = false;
                }
            }
            if (Auth::user()->hasPermission('specialquiz_play')) {
                $quiz = SpecialQuiz::where('date', $now->format('Y-m-d'))->first();
                if ($quiz) {
                    $response->special_quiz = !$quiz->isSubmitted();
                } else {
                    $response->special_quiz = false;
                }
                $yesterdayQuiz = SpecialQuiz::where('date', Carbon::yesterday()->format('Y-m-d'))
                    ->first();
                if ($yesterdayQuiz) {
                    $results = $yesterdayQuiz->getResult();
                    if ($results) {
                        $winners = [];
                        foreach ($results['ranking'] as $player) {
                            if ($player['rank'] === 1) {
                                array_push($winners, $player['user_id']);
                            } elseif (!array_key_exists('user', $input)) {
                                break;
                            }
                            if (
                                    array_key_exists('user', $input) &&
                                    $player['user_id'] === intval($input['user'])
                                ) {
                                $item->user_rank = $player['rank'];
                            }
                        }
                        $response->special_quiz_yesterday = [
                            'subject' => $yesterdayQuiz->subject,
                            'date' => $yesterdayQuiz->date,
                            'winners' => $winners,
                        ];
                    }
                }
            }
            $response->now = $now->format('Y-m-d');
        }
        return $this->sendResponse($response, 200);
    }

    public function create(Request $request)
    {
        if (Auth::user()->hasPermission('notifications_create')) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'content' => 'required|string',
                'type' => 'required|in:info,warning,danger',
                'start_date' => 'required|date_format:Y-m-d H:i:s',
                'end_date' => 'required|date_format:Y-m-d H:i:s|after:start_date',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $notification = Notification::create($input);
            return $this->sendResponse($notification, 201);
        }

        return $this->sendError('no_permissions', [], 403);
    }

    public function update(Request $request)
    {
        if (Auth::user()->hasPermission('notifications_edit')) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'id' => 'required|exists:notifications,id',
                'content' => 'string',
                'type' => 'in:info,warning,danger',
                'start_date' => 'date_format:Y-m-d H:i:s',
                'end_date' => 'date_format:Y-m-d H:i:s|after:start_date',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $notification = Notification::find($input['id']);
            $notification->fill($input);
            $notification->save();
            return $this->sendResponse($notification, 201);
        }
        return $this->sendError('no_permissions', [], 403);
    }

    public function delete(Request $request)
    {
        if (Auth::user()->hasPermission('notifications_delete')) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'id' => 'required|exists:notifications,id',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            Notification::find($input['id'])->delete();
            return $this->sendResponse();
        }

        return $this->sendError('no_permissions', [], 403);
    }
}
