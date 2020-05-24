<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use Request;
use Validator;
use Carbon\Carbon;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Notification;

class NotificationController extends BaseController
{
    public function get(Request $request)
    {
        if (Auth::user()->hasPermission('notifications_list')) {
            $notifications = Notification::orderBy('start_date', 'desc')->get();
        } else {
            $now = Carbon::now();
            $notifications = Notification::whereDate('start_date', '<', $now)
                ->whereDate('end_date', '>', $now)
                ->orderBy('start_date', 'desc')
                ->get();
        }
        return $this->sendResponse($notifications, 200);
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
