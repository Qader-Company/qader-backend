@extends('mail.layout')

@section('title', __('job_forms.mail.subject'))
@section('preheader', $existingAccount ? __('job_forms.mail.existing_account') : __('job_forms.mail.new_account'))

@section('content')
    <h1 class="email-title">{{ __('job_forms.mail.subject') }}</h1>

    <div class="email-text">
        <p>{{ __('job_forms.mail.greeting', ['name' => $name]) }}</p>
        <p>{{ $existingAccount ? __('job_forms.mail.existing_account') : __('job_forms.mail.new_account') }}</p>
    </div>

    <div class="info-box">
        <p class="info-label">{{ __('job_forms.mail.email') }}</p>
        <p class="info-value" dir="ltr">{{ $email }}</p>

        @if ($temporaryPassword)
            <div style="height:1px;background-color:#dbe4ff;margin:18px 0;"></div>
            <p class="info-label">{{ __('job_forms.mail.temporary_password') }}</p>
            <p class="info-value" dir="ltr">{{ $temporaryPassword }}</p>
        @endif
    </div>

    @if ($temporaryPassword)
        <div class="notice-box">{{ __('job_forms.mail.change_password') }}</div>
    @endif

    <p class="email-text" style="margin-top:22px;margin-bottom:0;">
        {{ __('job_forms.mail.complete_profile') }}
    </p>
@endsection
