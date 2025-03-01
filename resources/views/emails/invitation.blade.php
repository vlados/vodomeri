<x-mail::message>
# Invitation to Vodomeri

You have been invited to join the Vodomeri Water Meter Tracking System for **Apartment {{ $apartmentNumber }}**.

With this system, you will be able to:
- Submit water meter readings
- View your consumption history 
- Access historical billing information

Click the button below to accept the invitation and create your account:

<x-mail::button :url="$invitationUrl">
Accept Invitation
</x-mail::button>

This invitation will expire on: **{{ $expiresAt }}**

If you have any questions, please contact your building administrator.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>