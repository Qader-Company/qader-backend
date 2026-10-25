<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<body>
<p>{{ __('workspaces.member_mail.greeting', ['name' => $name]) }}</p>
<p>{{ $existingAccount
    ? __('workspaces.member_mail.existing_account', ['workspace' => $workspaceName])
    : __('workspaces.member_mail.new_account', ['workspace' => $workspaceName]) }}</p>
<p>{{ __('workspaces.member_mail.email') }}: <strong>{{ $email }}</strong></p>
@if ($temporaryPassword)
    <p>{{ __('workspaces.member_mail.temporary_password') }}: <strong>{{ $temporaryPassword }}</strong></p>
    <p>{{ __('workspaces.member_mail.change_password') }}</p>
@endif
<p>{{ __('workspaces.member_mail.sign_in') }}</p>
</body>
</html>
