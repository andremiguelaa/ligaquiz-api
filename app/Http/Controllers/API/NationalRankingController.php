<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use Request;
use Validator;
use Carbon\Carbon;
use App\Http\Controllers\API\BaseController as BaseController;
use App\IndividualQuiz;
use App\IndividualQuizResult;

class NationalRankingController extends BaseController
{
    public function get(Request $request)
    {
        $input = $request::all();
        if (array_key_exists('month', $input)) {
            $endDate = Carbon::createFromFormat('Y-m-d', $input['month'] . '-01');
            $startDate = (clone $endDate)->subMonths(11);

            $individualQuizzes = IndividualQuiz::whereBetween(
                'date',
                [
                    $startDate->format('Y-m-d'),
                    $endDate->format('Y-m-d')
                ]
            )
            ->select('id', 'date', 'individual_quiz_type')
            ->orderBy('date', 'asc')
            ->get();

            if (!count($individualQuizzes)) {
                return $this->sendError('not_found', null, 404);
            }

            foreach ($individualQuizzes as $individualQuiz) {
                $individualQuiz->results = $individualQuiz->results;
            }

            $rankingPlayers = array_reduce($individualQuizzes->toArray(), function ($acc, $individualQuiz) {
                foreach ($individualQuiz['results'] as $result) {
                    if (!array_key_exists($result['individual_quiz_player_id'], $acc)) {
                        $acc[$result['individual_quiz_player_id']] = (object) [
                            'individual_quiz_player_id' => $result['individual_quiz_player_id'],
                            'rank' => null,
                            'score' => 0,
                            'months' => []
                        ];
                    }
                    $acc[$result['individual_quiz_player_id']]->score += $result['score'];
                    $month = substr($individualQuiz['date'], 0, -3);
                    if (!array_key_exists($month, $acc[$result['individual_quiz_player_id']]->months)) {
                        $acc[$result['individual_quiz_player_id']]->months[$month] = [];
                    }
                    array_push($acc[$result['individual_quiz_player_id']]->months[$month], $result);
                }
                return $acc;
            }, []);

            usort($rankingPlayers, function ($a, $b) {
                return strcmp($b->score, $a->score);
            });

            $rank = 1;
            foreach ($rankingPlayers as $key => $player) {
                if (!($key > 0 && $rankingPlayers[$key - 1]->score === $rankingPlayers[$key]->score)) {
                    $rank = $key + 1;
                }
                $player->rank = $rank;
            }

            $ranking = array_values($rankingPlayers);

            $quizzes = array_reduce($individualQuizzes->toArray(), function ($acc, $individualQuiz) {
                if (!array_key_exists($individualQuiz['date'], $acc)) {
                    $acc[$individualQuiz['date']] = [];
                }
                array_push($acc[$individualQuiz['date']], $individualQuiz['individual_quiz_type']);
                return $acc;
            }, []);

            $response = (object) [
                'ranking' => $ranking,
                'quizzes' => $quizzes
            ];
        } else {
            $response = array_map(function ($individualQuiz) {
                return substr($individualQuiz['date'], 0, -3);
            }, IndividualQuiz::select('date')->distinct()->orderBy('date', 'desc')->get()->toArray());
        }
        return $this->sendResponse($response, 200);
    }

    public function create(Request $request)
    {
        if (Auth::user()->hasPermission('national_ranking_create')) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'month' => 'required|date_format:Y-m',
                'individual_quizzes' => 'required|array',
            ]);

            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            
            foreach ($input['individual_quizzes'] as $individualQuiz) {
                $individualQuiz['date'] = $input['month'] . '-01';
                $individualQuizValidator = Validator::make($individualQuiz, [
                    'individual_quiz_type' => 'required|exists:individual_quiz_types,slug|unique_with:individual_quizzes,date',
                    'results' => 'required|array',
                ]);
                if ($individualQuizValidator->fails()) {
                    return $this->sendError('validation_error', ['individual_quizzes' => 'validation.format'], 400);
                }
                foreach ($individualQuiz['results'] as $result) {
                    $resultValidator = Validator::make($result, [
                        'individual_quiz_player_id' => 'required|exists:individual_quiz_players,id',
                        'result' => 'required|integer',
                    ]);
                    if ($resultValidator->fails()) {
                        return $this->sendError('validation_error', ['individual_quizzes' => 'validation.format'], 400);
                    }
                }
            }

            foreach ($input['individual_quizzes'] as $individualQuiz) {
                $individualQuiz['date'] = $input['month'] . '-01';
                $newIndividualQuiz = IndividualQuiz::create($individualQuiz);
                foreach ($individualQuiz['results'] as $result) {
                    $result['individual_quiz_id'] = $newIndividualQuiz->id;
                    IndividualQuizResult::create($result);
                }
            }
            $individualQuizzes = IndividualQuiz::select('id', 'individual_quiz_type', 'date')->where('date', $input['month'] . '-01')->get();
            foreach ($individualQuizzes as $individualQuiz) {
                unset($individualQuiz->date);
                $individualQuiz->results = $individualQuiz->results;
            }
            return $this->sendResponse($individualQuizzes, 201);
        }

        return $this->sendError('no_permissions', [], 403);
    }

    public function update(Request $request)
    {
        if (Auth::user()->hasPermission('national_ranking_edit')) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'month' => 'required|date_format:Y-m',
                'individual_quizzes' => 'required|array',
            ]);

            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            
            foreach ($input['individual_quizzes'] as $individualQuiz) {
                $individualQuiz['date'] = $input['month'] . '-01';
                $individualQuizValidator = Validator::make($individualQuiz, [
                    'individual_quiz_type' => 'required|exists:individual_quiz_types,slug',
                    'results' => 'required|array',
                ]);
                if ($individualQuizValidator->fails()) {
                    return $this->sendError('validation_error', ['individual_quizzes' => 'validation.format'], 400);
                }
                foreach ($individualQuiz['results'] as $result) {
                    $resultValidator = Validator::make($result, [
                        'individual_quiz_player_id' => 'required|exists:individual_quiz_players,id',
                        'result' => 'required|integer',
                    ]);
                    if ($resultValidator->fails()) {
                        return $this->sendError('validation_error', ['individual_quizzes' => 'validation.format'], 400);
                    }
                }
            }

            $oldIndividualQuizzes = IndividualQuiz::where('date', $input['month'].'-01')->get();
            foreach ($oldIndividualQuizzes as $oldIndividualQuiz) {
                IndividualQuizResult::where('individual_quiz_id', $oldIndividualQuiz->id)->delete();
                $oldIndividualQuiz->delete();
            }         
            foreach ($input['individual_quizzes'] as $individualQuiz) {
                $individualQuiz['date'] = $input['month'] . '-01';
                $newIndividualQuiz = IndividualQuiz::create($individualQuiz);
                foreach ($individualQuiz['results'] as $result) {
                    $result['individual_quiz_id'] = $newIndividualQuiz->id;
                    IndividualQuizResult::create($result);
                }
            }
            $individualQuizzes = IndividualQuiz::select('id', 'individual_quiz_type', 'date')->where('date', $input['month'] . '-01')->get();
            foreach ($individualQuizzes as $individualQuiz) {
                $individualQuiz->results = $individualQuiz->results;
                unset($individualQuiz->date);
                unset($individualQuiz->id);
            }
            return $this->sendResponse($individualQuizzes, 201);
        }
        return $this->sendError('no_permissions', [], 403);
    }

    public function delete(Request $request)
    {
        if (Auth::user()->hasPermission('individual_quiz_delete')) {
            $input = $request::all();
            if (array_key_exists('month', $input)) {
                $input['month'] = $input['month'].'-01';
            }
            $validator = Validator::make($input, [
                'month' => 'required|exists:individual_quizzes,date',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $individualQuizzes = IndividualQuiz::where('date', $input['month'])->get();
            foreach ($individualQuizzes as $individualQuiz) {
                IndividualQuizResult::where('individual_quiz_id', $individualQuiz->id)->delete();
            }
            IndividualQuiz::where('date', $input['month'])->delete();
            return $this->sendResponse();
        }

        return $this->sendError('no_permissions', [], 403);
    }
}
