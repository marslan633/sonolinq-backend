{{-- <x-mail::message>
Hi {{ $details['details']['full_name'] }},

Your password has been changed and your new password is <strong>{{ $details['details']['password'] }}</strong>.

<x-mail::button :url="$details['details']['url']">
Login
</x-mail::button>

If you have any questions or need assistance, please feel free to contact us.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message> --}}

@if ($details['type'] == 'forgot-password')
    @php
        $data = str_replace('{{username}}', $details['full_name'], $details['body']);
        $data = str_replace('{{url}}', $details['url'], $data);
        $data = str_replace('{{password}}', $details['password'], $data);
    @endphp


<x-mail::message>
    {!! html_entity_decode($data) !!}
</x-mail::message>
@endif