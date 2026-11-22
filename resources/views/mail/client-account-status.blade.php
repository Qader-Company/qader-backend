@extends('mail.layout')

@php($subject = __($suspended ? 'clients.status_mail.suspended_subject' : 'clients.status_mail.reactivated_subject'))

@section('title', $subject)
@section('preheader', __($suspended ? 'clients.status_mail.suspended_body' : 'clients.status_mail.reactivated_body'))

@section('content')
    <h1 class="email-title">{{ $subject }}</h1>

    <div class="email-text">
        <p>{{ __('clients.status_mail.greeting', ['name' => $name]) }}</p>
        <p>{{ __($suspended ? 'clients.status_mail.suspended_body' : 'clients.status_mail.reactivated_body') }}</p>
    </div>

    @if ($suspended && filled($reason))
        <div class="info-box">
            <p class="info-label">{{ __('clients.status_mail.reason') }}</p>
            <p class="info-value">{{ $reason }}</p>
        </div>
    @endif

    <p class="email-text" style="margin-top:22px;margin-bottom:0;">
        {{ __($suspended ? 'clients.status_mail.suspended_contact' : 'clients.status_mail.sign_in') }}
    </p>
@endsection
