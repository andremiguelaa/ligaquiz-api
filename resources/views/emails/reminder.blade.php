@extends('emails.layout.base')

@section('content')

    @lang('mails.hello'), {{ $user->name }}!
    @if($quiz)
        <br>
        <br>
        @if($type === 'daily')
            @lang('mails.quiz_available')<br>
        @else
            @lang('mails.quiz_available_deadline')<br>
        @endif
        @if ($opponent)
            @lang('mails.quiz_opponent'): {{ $opponent->name }} {{ $opponent->surname }}<br>
        @endif
        @lang('mails.quiz_link')
    @endif
    @if($special_quiz)
        <br>
        <br>
        @if($type === 'daily')
            @lang('mails.specialquiz_available')<br>
        @else
            @lang('mails.specialquiz_available_deadline')<br>
        @endif
        @lang('mails.specialquiz_subject'): {{ $special_quiz->subject }}<br>
        @if($special_quiz->author)
            @lang('mails.specialquiz_author'): {{ $special_quiz->author }}<br>
        @endif
        @lang('mails.specialquiz_link')
    @endif
    @if($type === 'daily')
        <br>
        <br>
        @lang('mails.deadline')
    @endif

@endsection
