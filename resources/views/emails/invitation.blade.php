<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
    @lang('mails.hello')!<br>
    <br>
    @lang('mails.invite_message', ['name' => $user->name.' '.$user->surname])<br>
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
