@extends('emails.layout.base')

@section('content')

    @lang('mails.hello'), {{ $user->name }}!<br>
    <br>
    @lang('mails.quiz_submission_copy')<br>
    @foreach ($questions as $question)
        <br>
        <hr>
        <br>
        <b>@lang('mails.quiz_question') {{ $loop->index + 1 }}</b><br>
        {!! $question['content'] !!}
        @if ($question->media_id)
        @switch($media[$question->media_id]['type'])
            @case('image')
                <img style="max-width: 300px" alt="" src="{{ $media[$question->media_id]['url'] }}" />
                @break
            @case('audio')
                <audio controls preload="none" src="{{ $media[$question->media_id]['url'] }}"></audio>
                @break
            @case('video')
                <video controls preload="none" src="{{ $media[$question->media_id]['url'] }}"></video>
                @break
            @default                
        @endswitch
        <br>
        <br>
        @endif
        <div>
            <b>@lang('mails.correct_answer'):</b> {{ $question['answer'] }}<br>
            <b>@lang('mails.your_answer'):</b> {{ $submittedAnswers[$question->id]['text'] ? $submittedAnswers[$question->id]['text'] : '-' }}
        </div>
    @endforeach

@endsection
