<!DOCTYPE html>
<html lang="en">
<body>
<p>{{ __('job_forms.mail.greeting', ['name' => $name]) }}</p>
<p>{{ $existingAccount ? __('job_forms.mail.existing_account') : __('job_forms.mail.new_account') }}</p>
<p>{{ __('job_forms.mail.email') }}: {{ $email }}</p>
@if ($temporaryPassword)
<p>{{ __('job_forms.mail.temporary_password') }}: <strong>{{ $temporaryPassword }}</strong></p>
<p>{{ __('job_forms.mail.change_password') }}</p>
@endif
<p>{{ __('job_forms.mail.complete_profile') }}</p>
</body>
</html>
