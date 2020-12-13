@extends('emails.layout.base')

@section('content')

    @lang('mails.hello')!<br>
    <br>
    @lang('mails.invite_message', ['name' => $user->name.' '.$user->surname])

@endsection
