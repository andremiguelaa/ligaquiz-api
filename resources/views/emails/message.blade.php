@extends('emails.layout.base')

@section('content')

    @lang('mails.from'): {{ $user->name }} {{ $user->surname }} ({{ $user->email }})<br>
    <br>
    <?php echo nl2br(e($text)); ?>

@endsection
