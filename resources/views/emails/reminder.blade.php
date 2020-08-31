<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
    @lang('mails.hello'), {{ $user->name }}!<br>
    @if($quiz)
        <br>
        @if($type === 'daily')
            @lang('mails.quiz_available')<br>
        @else
            @lang('mails.quiz_available_deadline')<br>
        @endif
        @if ($opponent)
            @lang('mails.quiz_opponent'): {{ $opponent->name }} {{ $opponent->surname }}<br>
        @endif
        @lang('mails.quiz_link')<br>
    @endif
    @if($special_quiz)
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
        @lang('mails.specialquiz_link')<br>
    @endif
    <br>
    @if($type === 'daily')
        @lang('mails.deadline')
        <br>
        <br>
    @endif
    <hr>
    <br>
    <table>
        <tr>
            <td>
                <a href="https://ligaquiz.pt">
                    <img src="https://ligaquiz.pt/logo.png" alt="Liga Quiz">
                </a>
            </td>
        </tr>
    </table>
</body>
</html>

