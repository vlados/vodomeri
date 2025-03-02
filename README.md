# Водомери

Приложение за следене на показания на водомери.

## Функционалности

- Създаване и управление на апартаменти
- Добавяне на водомери
- Въвеждане на показания на водомери
- Следене на история на показанията
- Интеграция с OpenAI Vision API за автоматична верификация на показания чрез снимки

## Инсталация

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
npm run build
```

## Конфигурация

Задайте следните променливи в `.env` файла:

```
APP_NAME=Водомери
APP_LOCALE=bg

# OpenAI API Configuration
OPENAI_API_KEY=your-openai-api-key
OPENAI_ORGANIZATION=your-organization-id-if-any
```

## Стартиране

```bash
php artisan serve
```

## Тестване

```bash
./vendor/bin/pest
```

## Лиценз

Авторски права © 2025