@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/verify_email.css') }}">
@endsection

@section('content')

<main class="verify-container">

    <p class="verify-text">
        登録していただいたメールアドレスに認証メールを送付しました。<br>
        メール認証を完了してください。
    </p>

    <a href="http://localhost:8026/" class="verify-button">
        認証はこちらから
    </a>

    {{-- 認証メール再送 --}}
    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="resend-button">
            認証メールを再送する
        </button>
    </form>

</main>
@endsection