@extends('emails.layout.base')

@section('content')

    {{ $user->name }} {{ $user->surname }} ({{ $user->email }})

@endsection
