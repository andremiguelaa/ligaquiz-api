<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Request;
use Validator;
use App\Http\Controllers\BaseController as BaseController;
use App\SpecialQuizProposal;
use App\SpecialQuiz;
use App\SpecialQuizQuestion;
use App\Question;
use App\Media;
use App\User;
use App\Mail\NewSpecialQuizProposal;

class SpecialQuizProposalController extends BaseController
{
    public function get(Request $request)
    {
        $input = $request::all();
        if (
            Auth::user()->hasPermission('specialquiz_proposal_list') ||
            (
                Auth::user()->hasPermission('specialquiz_proposal_create') && 
                array_key_exists('draft', $input)
            )
        ) {
            $validator = Validator::make($input, [
                'id' => 'exists:special_quiz_proposals,id',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }            
            if (isset($input['id']) || array_key_exists('draft', $input)) {
                if (isset($input['id'])) {
                    $quiz = SpecialQuizProposal::find($input['id']);
                } else {
                    $quiz = SpecialQuizProposal::where('draft', 1)
                        ->where('user_id', Auth::user()->id)
                        ->first();
                }
                if (!$quiz) {
                    return $this->sendError('not_found', [], 404);
                }
                $questions = [
                    0 => [
                        'content' => $quiz->content_1,
                        'answer' => $quiz->answer_1,
                        'media_id' => $quiz->media_1_id
                    ],
                    1 => [
                        'content' => $quiz->content_2,
                        'answer' => $quiz->answer_2,
                        'media_id' => $quiz->media_2_id
                    ],
                    2 => [
                        'content' => $quiz->content_3,
                        'answer' => $quiz->answer_3,
                        'media_id' => $quiz->media_3_id
                    ],
                    3 => [
                        'content' => $quiz->content_4,
                        'answer' => $quiz->answer_4,
                        'media_id' => $quiz->media_4_id
                    ],
                    4 => [
                        'content' => $quiz->content_5,
                        'answer' => $quiz->answer_5,
                        'media_id' => $quiz->media_5_id
                    ],
                    5 => [
                        'content' => $quiz->content_6,
                        'answer' => $quiz->answer_6,
                        'media_id' => $quiz->media_6_id
                    ],
                    6 => [
                        'content' => $quiz->content_7,
                        'answer' => $quiz->answer_7,
                        'media_id' => $quiz->media_7_id
                    ],
                    7 => [
                        'content' => $quiz->content_8,
                        'answer' => $quiz->answer_8,
                        'media_id' => $quiz->media_8_id
                    ],
                    8 => [
                        'content' => $quiz->content_9,
                        'answer' => $quiz->answer_9,
                        'media_id' => $quiz->media_9_id
                    ],
                    9 => [
                        'content' => $quiz->content_10,
                        'answer' => $quiz->answer_10,
                        'media_id' => $quiz->media_10_id
                    ],
                    10 => [
                        'content' => $quiz->content_11,
                        'answer' => $quiz->answer_11,
                        'media_id' => $quiz->media_11_id
                    ],
                    11 => [
                        'content' => $quiz->content_12,
                        'answer' => $quiz->answer_12,
                        'media_id' => $quiz->media_12_id
                    ],
                ];
                $mediaIds = array_column($questions, 'media_id');
                $media = array_reduce(
                    Media::whereIn('id', $mediaIds)->get()->toArray(),
                    function ($carry, $item) {
                        $mediaFile = $item;
                        unset($mediaFile['id']);
                        $carry[$item['id']] = $mediaFile;
                        return $carry;
                    },
                    []
                );
                $proposal = (object)[];
                $proposal->subject = $quiz->subject;
                $proposal->description = $quiz->description;
                $proposal->user_id = $quiz->user_id;
                $proposal->questions = $questions;
                $response = ['quiz' => $proposal, 'media' => $media];
            } else {
                $response = SpecialQuizProposal::select('id', 'user_id', 'subject')
                    ->where('draft', 0)
                    ->get();
            }
            return $this->sendResponse($response, 200);
        }
        return $this->sendError('no_permissions', [], 403);
    }

    public function create(Request $request)
    {
        if (Auth::user()->hasPermission('specialquiz_proposal_create')) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'subject' => 'nullable|string',
                'description' => 'nullable|string',
                'questions' => 'array|size:12',
                'questions.*.content' => 'nullable|string',
                'questions.*.answer' => 'nullable|string',
                'questions.*.media_id' => 'nullable|exists:media,id',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $proposal = SpecialQuizProposal::create([
                'draft' => 1,
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

    public function update(Request $request)
    {
        if (Auth::user()->hasPermission('specialquiz_proposal_create')) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'subject' => 'nullable|string',
                'description' => 'nullable|string',
                'questions' => 'nullable|array|size:12',
                'questions.*.content' => 'nullable|string',
                'questions.*.answer' => 'nullable|string',
                'questions.*.media_id' => 'nullable|exists:media,id',
                'draft' => 'required|boolean'
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $proposal = SpecialQuizProposal::where('draft', 1)
                ->where('user_id', Auth::user()->id)
                ->first();
            if (!$proposal) {
                return $this->sendError('not_found', [], 404);
            }
            $proposal->draft = $input['draft'];
            $proposal->subject = $input['subject'];
            $proposal->description = isset($input['description']) ? $input['description'] : null;
            $proposal->content_1 = $input['questions'][0]['content'];
            $proposal->answer_1 = $input['questions'][0]['answer'];
            $proposal->media_1_id = isset($input['questions'][0]['media_id']) ?
                $input['questions'][0]['media_id'] : null;
            $proposal->content_2 = $input['questions'][1]['content'];
            $proposal->answer_2 = $input['questions'][1]['answer'];
            $proposal->media_2_id = isset($input['questions'][1]['media_id']) ?
                $input['questions'][1]['media_id'] : null;
            $proposal->content_3 = $input['questions'][2]['content'];
            $proposal->answer_3 = $input['questions'][2]['answer'];
            $proposal->media_3_id = isset($input['questions'][2]['media_id']) ?
                $input['questions'][2]['media_id'] : null;
            $proposal->content_4 = $input['questions'][3]['content'];
            $proposal->answer_4 = $input['questions'][3]['answer'];
            $proposal->media_4_id = isset($input['questions'][3]['media_id']) ?
                $input['questions'][3]['media_id'] : null;
            $proposal->content_5 = $input['questions'][4]['content'];
            $proposal->answer_5 = $input['questions'][4]['answer'];
            $proposal->media_5_id = isset($input['questions'][4]['media_id']) ?
                $input['questions'][4]['media_id'] : null;
            $proposal->content_6 = $input['questions'][5]['content'];
            $proposal->answer_6 = $input['questions'][5]['answer'];
            $proposal->media_6_id = isset($input['questions'][5]['media_id']) ?
                $input['questions'][5]['media_id'] : null;
            $proposal->content_7 = $input['questions'][6]['content'];
            $proposal->answer_7 = $input['questions'][6]['answer'];
            $proposal->media_7_id = isset($input['questions'][6]['media_id']) ?
                $input['questions'][6]['media_id'] : null;
            $proposal->content_8 = $input['questions'][7]['content'];
            $proposal->answer_8 = $input['questions'][7]['answer'];
            $proposal->media_8_id = isset($input['questions'][7]['media_id']) ?
                $input['questions'][7]['media_id'] : null;
            $proposal->content_9 = $input['questions'][8]['content'];
            $proposal->answer_9 = $input['questions'][8]['answer'];
            $proposal->media_9_id = isset($input['questions'][8]['media_id']) ?
                $input['questions'][8]['media_id'] : null;
            $proposal->content_10 = $input['questions'][9]['content'];
            $proposal->answer_10 = $input['questions'][9]['answer'];
            $proposal->media_10_id = isset($input['questions'][9]['media_id']) ?
                $input['questions'][9]['media_id'] : null;
            $proposal->content_11 = $input['questions'][10]['content'];
            $proposal->answer_11 = $input['questions'][10]['answer'];
            $proposal->media_11_id = isset($input['questions'][10]['media_id']) ?
                $input['questions'][10]['media_id'] : null;
            $proposal->content_12 = $input['questions'][11]['content'];
            $proposal->answer_12 = $input['questions'][11]['answer'];
            $proposal->media_12_id = isset($input['questions'][11]['media_id']) ?
                $input['questions'][11]['media_id'] : null;
            $proposal->save();
            if (!$input['draft']) {
                $possibleAdmins = User::where('roles', 'like', '%admin%')->get();
                $adminEmails = $possibleAdmins->reduce(function ($carry, $item) {
                    if ($item->isAdmin()) {
                        array_push($carry, $item->email);
                    }
                    return $carry;
                }, []);
                Mail::bcc($adminEmails)
                    ->locale(isset($input['language']) ? $input['language'] : 'en')
                    ->send(new NewSpecialQuizProposal(Auth::user(), $proposal));
            }
            return $this->sendResponse($proposal, 200);
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
