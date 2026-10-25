<?php

// Gera public/css/tokens.css a partir de tokens.json (design system Bioma CD).
// Uso: php resources/design/build-tokens.php

$tokens = json_decode(file_get_contents(__DIR__.'/tokens.json'), true, flags: JSON_THROW_ON_ERROR);

$byName = [];
foreach ($tokens['color']['tokens'] as $t) {
    $byName[$t['name']] = $t['value'];
}

// Resolve aliases "{outro-token}" dentro do mesmo tema.
$resolve = function (string $value, string $theme) use (&$resolve, $byName): string {
    if (preg_match('/^\{(.+)\}$/', $value, $m)) {
        $target = $byName[$m[1]];
        $v = is_array($target) ? ($target[$theme] ?? $target['light']) : $target;

        return $resolve($v, $theme);
    }

    return $value;
};

$vars = ['light' => [], 'dark' => []];
foreach ($tokens['color']['tokens'] as $t) {
    foreach (['light', 'dark'] as $theme) {
        $v = is_array($t['value']) ? ($t['value'][$theme] ?? $t['value']['light']) : $t['value'];
        $vars[$theme][$t['name']] = $resolve($v, $theme);
    }
}
foreach ($tokens['shadow']['tokens'] as $t) {
    foreach (['light', 'dark'] as $theme) {
        $vars[$theme][$t['name']] = is_array($t['value']) ? ($t['value'][$theme] ?? $t['value']['light']) : $t['value'];
    }
}

$plain = [];
foreach (['spacing', 'radius', 'border', 'size'] as $family) {
    foreach ($tokens[$family]['tokens'] as $t) {
        $plain[$t['name']] = $t['value'];
    }
}
$plain['font-sans'] = $tokens['type']['families']['sans'];

$block = fn (array $kv) => implode("\n", array_map(fn ($k, $v) => "  --$k: $v;", array_keys($kv), $kv));

$css = "/* Bioma CD — gerado de resources/design/tokens.json. Não edite à mão. */\n";
$css .= ":root {\n  color-scheme: light dark;\n".$block($plain)."\n".$block($vars['light'])."\n}\n";
$css .= "@media (prefers-color-scheme: dark) {\n  :root:not([data-theme=\"light\"]) {\n".str_replace("\n  ", "\n    ", '  '.$block($vars['dark']))."\n  }\n}\n";
$css .= ":root[data-theme=\"dark\"] {\n".$block($vars['dark'])."\n}\n";

@mkdir(__DIR__.'/../../public/css', 0755, true);
file_put_contents(__DIR__.'/../../public/css/tokens.css', $css);
echo "public/css/tokens.css gerado.\n";
