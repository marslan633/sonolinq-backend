<x-mail::message>
Hi {{ $details['details']['full_name'] }},

You're almost ready to get started. Please click on the button below to verify your email address and enjoy exclusive labeling services with us!

<x-mail::button :url="$details['details']['url']">
Click to Verify
</x-mail::button>

If you have any questions or need assistance, please feel free to contact us.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
