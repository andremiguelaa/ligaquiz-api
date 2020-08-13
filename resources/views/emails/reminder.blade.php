<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
    @lang('mails.hello'), {{ $user->name }}!<br>
    <br>
    <a href="https://ligaquiz.pt/quiz">@lang('mails.quiz_availabe')</a><br>
    @if ($opponent)
        @lang('mails.quiz_opponent'): {{ $opponent->name }} {{ $opponent->surname }}
    @endif
    <br>
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

