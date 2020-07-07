<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use Request;
use Validator;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Game;
use App\Question;
use App\QuizQuestion;
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
            $query = Game::with('quiz', 'quiz.questions', 'quiz.questions.question');
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
            $gamesQuizzes = $games->unique('quiz.questions')
                ->pluck('quiz.questions')
                ->filter()
                ->toArray();
            
            $games = array_map(function ($game) {
                unset($game['quiz']['questions']);
                return $game;
            }, $games->toArray());
            
            $questionIds = [];
            $quizzes = array_map(function ($quiz) use(&$questionIds) {
                return array_map(function ($question) use(&$questionIds) {
                    array_push($questionIds, $question['question']['id']);
                    return $question['question'];
                }, $quiz);
            }, $gamesQuizzes);

            $answers = Answer::whereIn('question_id', $questionIds)
                ->where('submitted', 1)
                ->select('question_id', 'user_id', 'points', 'correct', 'corrected')
                ->get();

            return $this->sendResponse([
                'games' => $games,
                'quizzes' => $quizzes,
                'answers' => $answers
            ], 200);
        }
        
        return $this->sendError('no_permissions', [], 403);
    }
}
