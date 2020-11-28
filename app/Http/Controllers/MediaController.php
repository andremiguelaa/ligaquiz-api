<?php

namespace App\Http\Controllers;

use Request;
use Validator;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\BaseController as BaseController;
use App\Media;

class MediaController extends BaseController
{
    public function get()
    {
        if (
            Auth::user()->hasPermission('quiz_create') ||
            Auth::user()->hasPermission('quiz_edit') ||
            Auth::user()->hasPermission('specialquiz_create') ||
            Auth::user()->hasPermission('specialquiz_edit')
        ) {
            return $this->sendResponse(Media::all(), 200);
        }
        return $this->sendError('no_permissions', [], 403);
    }

    public function create(Request $request)
    {
        if (
            Auth::user()->hasPermission('quiz_create') ||
            Auth::user()->hasPermission('quiz_edit') ||
            Auth::user()->hasPermission('specialquiz_create') ||
            Auth::user()->hasPermission('specialquiz_edit')
        ) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'file' => 'required|file|mimetypes:video/mp4,image/png,image/gif,image/jpeg,audio/mpeg,mpga,mp3,wav'
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $filename = 'media_'.round(microtime(true) * 1000).'.'.$input['file']
                ->extension();
            $type = explode("/", $input['file']->getMimeType())[0];
            $path = $request::file('file')->storeAs(
                'media',
                $filename
            );
            $media = Media::create([
                'filename' => $path,
                'type' => $type
            ]);
            return $this->sendResponse($media, 201);
        }
        return $this->sendError('no_permissions', [], 403);
    }

    public function delete(Request $request)
    {
        if (
            Auth::user()->hasPermission('quiz_create') ||
            Auth::user()->hasPermission('quiz_edit') ||
            Auth::user()->hasPermission('specialquiz_create') ||
            Auth::user()->hasPermission('specialquiz_edit')
        ) {
            $input = $request::all();
            $validator = Validator::make($input, [
                'id' => 'required|exists:media',
            ]);
            if ($validator->fails()) {
                return $this->sendError('validation_error', $validator->errors(), 400);
            }
            $media = Media::find($input['id']);
            if ($media->isUsed()) {
                return $this->sendError('file_in_use', null, 400);
            }
            Media::where('id', $input['id'])->delete();
            return $this->sendResponse();
        }

        return $this->sendError('no_permissions', [], 403);
    }
}
