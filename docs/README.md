# Builder V2 Module Registry

This directory can contain additional Builder V2 modules.

Each module lives in its own subdirectory, for example:

- builder-v2/modules/example/

Supported files inside a module directory:

1. `module.php` (optional)
- Must return a PHP array with `label`, `icon`, `defaults`.
- Optional keys: `fields`, `render_callback`, `sanitize_callback`.

2. `module.json` (optional)
- JSON object merged over `module.php` values.

3. `defaults.php` (optional)
- Must return a PHP array.
- Merged into `defaults`.

Module id
- By default, the module id is the folder name.
- You can override with `id` in `module.php`/`module.json`.

Minimum definition example in module.php:

```php
<?php
return array(
    'label' => 'Example',
    'icon' => 'EX',
    'defaults' => array(
        'text' => 'Example content',
    ),
    'fields' => array(
        array(
            'key' => 'text',
            'label' => 'Text',
            'type' => 'textarea',
        ),
    ),
);
```

Field definitions
- `key`: option key inside `settings`
- `label`: human-readable field label
- `type`: `text`, `textarea`, `number`, `color`, `toggle`, `select`
- `options`: required for `select`, array of `{ value, label }`
- `min` / `max`: optional for `number`

Callbacks
- `render_callback($module, $global, $mode, $context, $builder)`
- `sanitize_callback($settings, $module, $state, $builder)`

If a `render_callback` returns a non-empty string, it is used as the module output.

WordPress hooks
- `enews_builder_v2_module_dirs`: add custom folders to scan.
- `enews_builder_v2_modules`: adjust full module map before use.
- `enews_builder_v2_presets`: add/modify template presets.
- `enews_builder_v2_rendered_module_content`: filter final rendered module HTML.
