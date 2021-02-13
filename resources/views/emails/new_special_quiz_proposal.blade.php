@extends('emails.layout.base')

@section('content')

    {{ $proposal->subject }} ({{ $user->name }} {{ $user->surname }})

@endsection
