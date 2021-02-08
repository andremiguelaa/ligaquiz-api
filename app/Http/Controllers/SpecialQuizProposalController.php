<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Request;
use Validator;
use App\Http\Controllers\BaseController as BaseController;
use App\SpecialQuizProposal;
use App\SpecialQuiz;
use App\SpecialQuizQuestion;
use App\Question;
use App\Media;

class SpecialQuizProposalController extends BaseController
{
    public function get(Request $request)
    {
        $input = $request::all();
        if (Auth::user()->hasPermission('specialquiz_proposal_list')) {
            $quizzes = SpecialQuizProposal::all();
            return $this->sendResponse($quizzes, 200);
        }
        return $this->sendError('no_permissions', [], 403);
    }

    public function create(Request $request)
    {
        if (Auth::user()->hasPermission('specialquiz_proposal_create')) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'subject' => 'required|string',
                'description' => 'nullable|string',
                'questions' => 'required|array|size:12',
                'questions.*.content' => 'required|string',
                'questions.*.answer' => 'required|string',
                'questions.*.media_id' => 'nullable|exists:media,id',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $proposal = SpecialQuizProposal::create([
                'user_id' => Auth::user()->id,
                'subject' => $input['subject'],
                'description' => isset($input['description']) ? $input['description'] : null,
                'content_1' => $input['questions'][0]['content'],
                'answer_1' => $input['questions'][0]['answer'],
                'media_1_id' => isset($input['questions'][0]['media_id']) ?
                    $input['questions'][0]['media_id'] : null,
                'content_2' => $input['questions'][1]['content'],
                'answer_2' => $input['questions'][1]['answer'],
                'media_2_id' => isset($input['questions'][1]['media_id']) ?
                    $input['questions'][1]['media_id'] : null,
                'content_3' => $input['questions'][2]['content'],
                'answer_3' => $input['questions'][2]['answer'],
                'media_3_id' => isset($input['questions'][2]['media_id']) ?
                    $input['questions'][2]['media_id'] : null,
                'content_4' => $input['questions'][3]['content'],
                'answer_4' => $input['questions'][3]['answer'],
                'media_4_id' => isset($input['questions'][3]['media_id']) ?
                    $input['questions'][3]['media_id'] : null,
                'content_5' => $input['questions'][4]['content'],
                'answer_5' => $input['questions'][4]['answer'],
                'media_5_id' => isset($input['questions'][4]['media_id']) ?
                    $input['questions'][4]['media_id'] : null,
                'content_6' => $input['questions'][5]['content'],
                'answer_6' => $input['questions'][5]['answer'],
                'media_6_id' => isset($input['questions'][5]['media_id']) ?
                    $input['questions'][5]['media_id'] : null,
                'content_7' => $input['questions'][6]['content'],
                'answer_7' => $input['questions'][6]['answer'],
                'media_7_id' => isset($input['questions'][6]['media_id']) ?
                    $input['questions'][6]['media_id'] : null,
                'content_8' => $input['questions'][7]['content'],
                'answer_8' => $input['questions'][7]['answer'],
                'media_8_id' => isset($input['questions'][7]['media_id']) ?
                    $input['questions'][7]['media_id'] : null,
                'content_9' => $input['questions'][8]['content'],
                'answer_9' => $input['questions'][8]['answer'],
                'media_9_id' => isset($input['questions'][8]['media_id']) ?
                    $input['questions'][8]['media_id'] : null,
                'content_10' => $input['questions'][9]['content'],
                'answer_10' => $input['questions'][9]['answer'],
                'media_10_id' => isset($input['questions'][9]['media_id']) ?
                    $input['questions'][9]['media_id'] : null,
                'content_11' => $input['questions'][10]['content'],
                'answer_11' => $input['questions'][10]['answer'],
                'media_11_id' => isset($input['questions'][10]['media_id']) ?
                    $input['questions'][10]['media_id'] : null,
                'content_12' => $input['questions'][11]['content'],
                'answer_12' => $input['questions'][11]['answer'],
                'media_12_id' => isset($input['questions'][11]['media_id']) ?
                    $input['questions'][11]['media_id'] : null,
            ]);
            return $this->sendResponse($proposal, 200);
        }
        return $this->sendError('no_permissions', [], 403);
    }

    public function publish(Request $request)
    {
        if (Auth::user()->hasPermission('specialquiz_proposal_publish')) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'id' => 'required|exists:special_quiz_proposals,id',
                'date' => 'required|date_format:Y-m-d|unique:special_quizzes',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $proposal = SpecialQuizProposal::find($input['id']);
            $quiz = SpecialQuiz::create([
                'date' => $input['date'],
                'user_id' => $proposal->user_id,
                'subject' => $proposal->subject,
                'description' => $proposal->description,
            ]);
            for ($i=1; $i <= 12; $i++) {
                $createdQuestion = Question::create([
                    'content' => $proposal['content_'.$i],
                    'answer' => $proposal['answer_'.$i],
                    'media_id' => $proposal['media_'.$i.'_id'],
                ]);
                SpecialQuizQuestion::create([
                    'special_quiz_id' => $quiz->id,
                    'question_id' => $createdQuestion->id
                ]);
            }
            $proposal->delete();
            $quiz = SpecialQuiz::with('questions.question')->find($quiz->id);
            $questions = $quiz->questions->map(function ($question) {
                return $question->question;
            });
            unset($quiz->questions);
            $quiz->questions = $questions;
            return $this->sendResponse($quiz, 200);
        }
        return $this->sendError('no_permissions', [], 403);
    }

    public function delete(Request $request)
    {
        if (Auth::user()->hasPermission('specialquiz_proposal_delete')) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'id' => 'required|exists:special_quiz_proposals,id',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            SpecialQuizProposal::find($input['id'])->delete();
            return $this->sendResponse();
        }
        return $this->sendError('no_permissions', [], 403);
    }
}
