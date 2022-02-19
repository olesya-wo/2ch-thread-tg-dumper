<?php

declare(strict_types=1);

$passed = 0; // Сколько успешных тест-кейсов в текущем файле
$failed = 0; // Сколько проваленных тест-кейсов в текущем файле
$compact = 0; // При массовом запуске тестов удобнее, чтобы выводились только FAIL
$total_passed = 0;
$total_failed = 0;
/*
Запуск тестов:
    Из текущей директории проекта ( не из папки tests )
    1. Один конкретный
         php tests/some_method.php
    2. Все имеющиеся
        php tests/tests.php run_tests
        или
        php tests/tests.php run_tests compact
            Флаг compact - не выводить сообщения об успешных тестах, только ошибки и итоговое сообщение
*/

// Собственный велосипед для assert
function _assert(bool $condition): void {
    if (!$condition) {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $line = $trace[0]['line'];
        throw new Exception("Line {$line}");
    }
}

// Обёртка для одного тест-кейса
function test(string $title, $callback): void {
    global $passed, $failed, $compact;
    try {
        $callback();
        if (!$compact) {
            echo "OK - {$title}\n";
        }
        $passed++;
    } catch (Exception $e) {
        $error = $e->getMessage();
        $error = $error ? " | {$error}" : '';
        echo "Fail - {$title}{$error}\n";
        $failed++;
    }
}

// Должен быть вызван в конце каждого файла-теста
// Выведет статистику и гарантирует, что все кейсы отработали
function end_tests(): void {
    global $passed, $failed;
    if (!$failed and $passed) {
        echo "{$passed} - ^_^\n";
    } elseif ($passed) {
        $total = $failed + $passed;
        echo "Uuu~ Failed {$failed}/{$total}\n";
    } elseif (!$failed) {
        echo "No tests\n";
    }
}

// Запуск всех тестов в папке tests
function run_tests(): void {
    global $failed, $passed, $total_failed, $total_passed;
    if ($handle = opendir('./tests/')) {
        while (false !== ($file = readdir($handle))) {
            if ($file == '.' or $file == '..' or $file == 'tests.php') {
                continue;
            }
            echo "{$file}\n";
            $failed = 0;
            $passed = 0;
            require_once $file;
            if (!$failed and $passed) {
                $total_passed++;
            }
            if ($failed) {
                $total_failed++;
            }
        }
        closedir($handle);
        if ($total_failed) {
            echo "\nFailed {$total_failed} test-files\n";
        } else {
            echo "\nAll {$total_passed} passed\n";
        }
    }
}

// Проверяем аргументы и запускаем тесты, если нужно
if (isset($argv[1]) and $argv[1] == 'run_tests') {
    if (isset($argv[2]) and $argv[2] == 'compact') {
        $compact = 1;
    }
    run_tests();
}
