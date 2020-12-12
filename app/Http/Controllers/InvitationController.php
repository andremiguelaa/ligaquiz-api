<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use Request;
use Validator;
use App\Http\Controllers\BaseController as BaseController;
use App\Invitation;
use App\Mail\Invitation as InvitationMessage;

class InvitationController extends BaseController
{
    public function get()
    {
        if (Auth::user()->hasPermission('quiz_play')) {
            $invitations = Invitation::where('user_id', Auth::user()->id)->get();
            return $this->sendResponse($invitations, 200);
        }
        return $this->sendError('no_permissions', [], 403);
    }
    
    public function send(Request $request)
    {
        if (Auth::user()->hasPermission('quiz_play')) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'email' => 'required|email|max:255|unique:users|unique:invitations',
                'language' => ['required', Rule::in(['en', 'pt'])],
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $invitation = Invitation::create([
                'user_id' => Auth::user()->id,
                'email' => $input['email'],
            ]);
            Mail::to($input['email'])
                ->locale($input['language'])
                ->send(new InvitationMessage(Auth::user()));
            return $this->sendResponse($invitation, 200);
        }
        return $this->sendError('no_permissions', [], 403);
    }

    public function resend(Request $request)
    {
        if (Auth::user()->hasPermission('quiz_play')) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'id' => 'required|exists:invitations',
                'language' => ['required', Rule::in(['en', 'pt'])],
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $invitation = Invitation::find($input['id']);
            $invitation->touch();
            Mail::to($invitation->email)
                ->locale($input['language'])
                ->send(new InvitationMessage(Auth::user()));
            return $this->sendResponse($invitation, 200);
        }
        return $this->sendError('no_permissions', [], 403);
    }
}
