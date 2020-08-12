<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
    @lang('mails.from'): {{ $user->name }} {{ $user->surname }} ({{ $user->email }})
    <br>
    <br>
    <?php echo nl2br(e($text)); ?>
    <br>
    <br>
    <hr>
    <br>
    <table>
        <tr>
            <td>
                <a href="https://ligaquiz.pt">
                    <img src="https://ligaquiz.pt/img/logo.png" alt="Liga Quiz">
                </a>
            </td>
        </tr>
    </table>
</body>
</html>
