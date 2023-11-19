<x-mail::message>
    Hi {{ $details['details']['full_name'] }},

    Thank you for registering with our website! Your account has been successfully created.

    Your Login Credentials;

    <strong>
        Email => {{ $details['details']['email'] }} <br>
        Password => {{ $details['details']['password'] }}
    </strong>

    <x-mail::button :url="$details['details']['url']">
        Visit Our Website
    </x-mail::button>

    If you have any questions or need assistance, please feel free to contact us.

    Thanks,<br>
    {{ config('app.name') }}
</x-mail::message>
