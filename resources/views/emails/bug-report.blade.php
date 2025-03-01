<x-mail::message>
# Нов доклад за грешка: {{ $bug->title }}

Потребител **{{ $bug->user->name }}** ({{ $bug->user->email }}) докладва нова грешка.

## Описание
{{ $bug->description }}

@if ($bug->steps_to_reproduce)
## Стъпки за възпроизвеждане
{{ $bug->steps_to_reproduce }}
@endif

@if ($bug->browser_info)
## Информация за браузъра
{{ $bug->browser_info }}
@endif

## Дата и час
{{ $bug->created_at->format('d.m.Y H:i:s') }}

<x-mail::button :url="config('app.url') . '/admin'">
Преглед в администрацията
</x-mail::button>

Поздрави,<br>
{{ config('app.name') }}
</x-mail::message>