<?php

function filesRecursively(string $directory, string $suffix): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && str_ends_with($file->getFilename(), $suffix)) {
            $files[] = $file->getPathname();
        }
    }

    sort($files);

    return $files;
}

test('blade templates do not render unescaped resolution variables', function () {
    $views = filesRecursively(__DIR__.'/../../resources/views', '.blade.php');

    expect($views)->not->toBeEmpty();

    foreach ($views as $view) {
        $content = (string) file_get_contents($view);

        // Disallow raw unescaped output of resolution
        expect($content)->not->toMatch('/\{!![\s\S]*?resolution[\s\S]*?!!\}/i');
    }
});

test('blade templates enforce noopener noreferrer on all target blank links', function () {
    $views = filesRecursively(__DIR__.'/../../resources/views', '.blade.php');

    expect($views)->not->toBeEmpty();

    foreach ($views as $view) {
        $content = (string) file_get_contents($view);

        preg_match_all('/<a\b[^>]*target=["\']_blank["\'][^>]*>/i', $content, $links);
        foreach ($links[0] as $link) {
            expect($link)->toMatch('/\brel=["\'][^"\']*\bnoopener\b[^"\']*\bnoreferrer\b[^"\']*["\']/i');
        }
    }
});

test('test files do not contain silent early returns (anti-tautology guard)', function () {
    $testFiles = filesRecursively(__DIR__.'/..', 'Test.php');

    foreach ($testFiles as $file) {
        $content = (string) file_get_contents($file);
        $lines = explode("\n", $content);

        foreach ($lines as $index => $line) {
            $trimmed = trim($line);
            // A naked return can silently turn a required integration test into a pass.
            if ($trimmed === 'return;' || $trimmed === 'return ;') {
                test()->fail("Test file [{$file}] line ".($index + 1).' contains a silent early return. Use test()->skip() with a reason instead.');
            }
        }
    }

    expect(true)->toBeTrue();
});
