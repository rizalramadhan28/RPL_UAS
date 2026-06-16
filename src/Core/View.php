<?php
declare(strict_types=1);

namespace App\Core {

    final class View
    {
        private static array $shared = [];

        public static function share(string $key, mixed $value): void
        {
            self::$shared[$key] = $value;
        }

        /**
         * Render a template with given variables.
         *
         * IMPORTANT: We use unique parameter names prefixed with __view_ to avoid
         * collisions with caller-provided keys (e.g. 'data', 'template'). After
         * EXTR_SKIP, any variable name that already exists in scope (including the
         * parameter names) would otherwise be silently skipped.
         */
        public static function render(string $__view_template, array $__view_vars = []): void
        {
            $__view_vars = array_merge(self::$shared, $__view_vars);
            $__view_path = __DIR__ . '/../../templates/' . $__view_template . '.php';
            if (!is_file($__view_path)) {
                throw new \RuntimeException("Template not found: {$__view_template}");
            }
            // Render in an isolated closure scope so parameter names cannot leak
            // into the template and the caller's keys map cleanly to variables.
            (static function (string $__path, array $__vars): void {
                extract($__vars, EXTR_SKIP);
                require $__path;
            })($__view_path, $__view_vars);
        }

        public static function partial(string $template, array $data = []): void
        {
            self::render($template, $data);
        }

        public static function e(?string $value): string
        {
            return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        }
    }

}

namespace {
    if (!function_exists('e')) {
        function e(?string $value): string {
            return \App\Core\View::e($value);
        }
    }
}
