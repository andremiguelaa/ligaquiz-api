<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use Request;
use Validator;
use App\Http\Controllers\BaseController as BaseController;
use App\User;
use App\Mail\Message;

class MessageController extends BaseController
{
    public function send(Request $request)
    {
        $input = $request::all();
        $validator = Validator::make($input, [
            'language' => ['required', Rule::in(['en', 'pt'])],
            'message' => 'required|string'
        ]);
        if ($validator->fails()) {
            return $this->sendError('validation_error', $validator->errors(), 400);
        }
        $data = array(
            'user' => Auth::user(),
            'body' => $input['message']
        );
        $possibleAdmins = User::where('roles', 'like', '%admin%')->get();
        $adminEmails = $possibleAdmins->reduce(function ($carry, $item) {
            if ($item->isAdmin()) {
                array_push($carry, $item->email);
            }
            return $carry;
        }, []);
        Mail::bcc($adminEmails)
            ->locale($input['language'])
            ->send(new Message(Auth::user(), $input['message']));
        return $this->sendResponse(null, 200);
    }
}
