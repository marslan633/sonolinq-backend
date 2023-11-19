<x-mail::message>
Hi {{ $details['details']['full_name'] }},

Your password has been changed and your new password is <strong>{{ $details['details']['password'] }}</strong>.

<x-mail::button :url="$details['details']['url']">
Login
</x-mail::button>

If you have any questions or need assistance, please feel free to contact us.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
