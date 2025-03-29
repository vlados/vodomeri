@component('mail::message')
# Покана за Водомери

Поканени сте да се присъедините към системата за отчитане на водомери Водомери за **Апартамент {{ $apartmentNumber }}**.

С тази система ще можете да:
- Подавате показания на водомери
- Преглеждате историята на консумацията си
- Достъпвате историческа информация за плащания

Натиснете бутона по-долу, за да приемете поканата и да създадете своя акаунт:

@component('mail::button', ['url' => $invitationUrl])
Приеми поканата
@endcomponent

Тази покана ще изтече на: **{{ $expiresAt }}**

Ако имате въпроси, моля, свържете се с администратора на сградата.

Поздрави,<br>
{{ config('app.name') }}
@endcomponent