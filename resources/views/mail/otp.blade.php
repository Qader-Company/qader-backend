@extends('mail.layout')

@section('title', $subject)
@section('preheader', __('otp.preheader'))

@section('content')
    <h1 class="email-title">{{ $subject }}</h1>

    <div class="email-text">
        <p>{{ $greeting }}</p>
        <p>{{ __('otp.code_intro') }}</p>
    </div>

    <div style="margin: 26px 0; padding: 24px; background-color: #f3f6ff; border: 1px solid #dbe4ff; border-radius: 14px; text-align: center;">
        <p class="info-label" style="text-align:center;">{{ __('otp.code_label') }}</p>
        <p style="margin:0;color:#0a32db;font-size:34px;line-height:42px;font-weight:700;letter-spacing:8px;direction:ltr;">
            {{ $code }}
        </p>
    </div>

    <div class="notice-box">
        {{ __('otp.expires', ['minutes' => $expiresInMinutes]) }}
    </div>

    <p class="email-text" style="margin-top:22px;margin-bottom:0;">
        {{ __('otp.ignore') }}
    </p>
@endsection
