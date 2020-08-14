<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
    @lang('mails.hello'), {{ $user->name }}!<br>
    @if($quiz)
        <br>
        @lang('mails.quiz_availabe')<br>
        @if ($opponent)
            @lang('mails.quiz_opponent'): {{ $opponent->name }} {{ $opponent->surname }}<br>
        @endif
        @lang('mails.quiz_link')<br>
    @endif
    @if($special_quiz)
        <br>
        @lang('mails.specialquiz_available')<br>
        @lang('mails.specialquiz_subject'): {{ $special_quiz->subject }}<br>
        @if($special_quiz->author)
            @lang('mails.specialquiz_author'): {{ $special_quiz->author }}<br>
        @endif
        @lang('mails.specialquiz_link')<br>
    @endif
    <br>
    @lang('mails.deadline')<br>
    <br>
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

