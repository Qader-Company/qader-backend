@extends('mail.layout')

@section('title', __('workspaces.member_mail.subject', ['workspace' => $workspaceName]))
@section('preheader', $existingAccount
    ? __('workspaces.member_mail.existing_account', ['workspace' => $workspaceName])
    : __('workspaces.member_mail.new_account', ['workspace' => $workspaceName]))

@section('content')
    <h1 class="email-title">{{ __('workspaces.member_mail.subject', ['workspace' => $workspaceName]) }}</h1>

    <div class="email-text">
        <p>{{ __('workspaces.member_mail.greeting', ['name' => $name]) }}</p>
        <p>{{ $existingAccount
            ? __('workspaces.member_mail.existing_account', ['workspace' => $workspaceName])
            : __('workspaces.member_mail.new_account', ['workspace' => $workspaceName]) }}</p>
    </div>

    <div class="info-box">
        <p class="info-label">{{ __('workspaces.member_mail.email') }}</p>
        <p class="info-value" dir="ltr">{{ $email }}</p>

        @if ($temporaryPassword)
            <div style="height:1px;background-color:#dbe4ff;margin:18px 0;"></div>
            <p class="info-label">{{ __('workspaces.member_mail.temporary_password') }}</p>
            <p class="info-value" dir="ltr">{{ $temporaryPassword }}</p>
        @endif
    </div>

    @if ($temporaryPassword)
        <div class="notice-box">{{ __('workspaces.member_mail.change_password') }}</div>
    @endif

    <p class="email-text" style="margin-top:22px;margin-bottom:0;">
        {{ __('workspaces.member_mail.sign_in') }}
    </p>
@endsection
