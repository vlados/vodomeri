# Vodomeri - Water Meter Tracking System

## Build/Run Commands
- `php artisan serve` - Start development server
- `npm run dev` - Compile assets and watch for changes
- `npm run build` - Build for production

## Testing Commands
- `./vendor/bin/pest` - Run all tests
- `./vendor/bin/pest tests/Feature/ExampleTest.php` - Run a single test file 
- `./vendor/bin/pest --filter=test_name` - Run tests matching filter

## Lint/Style Commands
- `./vendor/bin/pint` - Run Laravel Pint code style fixer

## Database Commands
- `php artisan migrate` - Run migrations
- `php artisan db:seed` - Seed database
- `php artisan migrate:fresh --seed` - Fresh database with seeds

## Code Style Guidelines
- PSR-4 autoloading standard
- Use type declarations (PHP 8.2+)
- Models with relationships should use relation return types
- Tests use Pest PHP testing framework
- Follow Laravel conventions for controller methods
- Organize imports alphabetically within groups
- Use constructor property promotion
- All database fields should be properly typed in migrations