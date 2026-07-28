# Webfox Block Builder

Reusable block editor for Laravel 12 and Orchid Platform 14.

## Install in a new project

```bash
composer require webfox/webfox-block-builder
php artisan block-builder:install
```

The install command publishes assets and `config/block-editor.php`, creates
`app/Blocks` and `resources/views/blocks` and does not overwrite existing project files.

Add each model that will use the editor to `block-editor.allowed_models`:

```php
'allowed_models' => [App\Models\Page::class],
```

Use the editor in an Orchid layout:

```php
BlockEditorField::make('blocks')->model($page),
```

Blocks are rendered with `@blocks($page, 'content', app()->getLocale())`.

## Project blocks

Project-owned block classes live in `app/Blocks` and Blade templates live in
`resources/views/blocks`. Classes named `*Block` are discovered automatically;
each class must expose `public static function register(): void` and register its
type through `Webfox\BlockEditor\BlockRegistry`.

Create a skeleton with:

```bash
php artisan make:block TextImage --category="Content" --description="Text and image"
```

Block data is stored as JSON in the selected model field, preserving the stable
`id`, `type`, `order`, `data`, and `settings` structure.

## Package development in webfox.dev

The package is kept in `packages/webfox/block-editor` as a Git submodule and is
installed by the application through a Composer `path` repository. Build release
assets from the package directory with `npm run build` before tagging a release.
