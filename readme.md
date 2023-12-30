## musabbir035/crud

This package generates code for simple CRUD operations.

### Installation

Require via composer

```
composer require --dev musabbir035/crud
```

### Usage

Generate code by using the following command

```
php artisan crud:generate Book
```

This command generates the model class, controller, validation and migration files. It also appends the routes for the CRUD operations of the created model in the 'routes/web.php' file & runs database migrations. <br>
It also accepts a `--fields=` option for the fields of the model.

```
php artisan crud:generate Book --fields="title,author_name"
```

You will need to create a layout file in resources/views/layouts/main.blade.php. Inside the body tag add `@yield('content')`. Bootstrap 5 is needed for styling.
