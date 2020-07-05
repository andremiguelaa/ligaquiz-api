<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use Request;
use Validator;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Game;
use App\Question;
use App\Answer;

class GameController extends BaseController
{
    public function get(Request $request)
    {
        if (Auth::user()->hasPermission('quiz_play')) {
            $input = $request::all();
            $rules = [
                'id' => 'exists:games,id',
                'season' => 'required_with:round|exists:seasons,season',
                'round' => 'integer|between:1,20',
                'user' => 'exists:users,id'
            ];
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $query = Game::with('quiz');
            foreach ($input as $key => $value) {
                if (in_array($key, array_keys($rules))) {
                    if ($key === 'user') {
                        $query->where(function ($userQuery) use ($value) {
                            $userQuery->where('user_id_1', $value)->orWhere('user_id_2', $value);
                        });
                    } else {
                        $query->where($key, $value);
                    }
                }
            }
            $games = $query->get();
            $quizzes = $games->unique('quiz.id')->pluck('quiz')->filter();
            $quizzesIds = [];
            $questionIds = [];
            foreach ($quizzes as $quiz) {
                array_push($quizzesIds, 'quiz_'.$quiz->id);
                $questionIds = array_merge($questionIds, array_values($quiz->question_ids));
            }
            $questions = Question::whereIn('id', $questionIds)->get();
            $answers = Answer::whereIn('question_id', $questionIds)
                ->where('submitted', 1)
                ->whereIn('quiz', $quizzesIds)
                ->select('question_id', 'user_id', 'quiz', 'points', 'correct', 'corrected')
                ->get();
            return $this->sendResponse([
                'games' => $games,
                'questions' => $questions,
                'answers' => $answers
            ], 200);
        }
        
        return $this->sendError('no_permissions', [], 403);
    }
}
