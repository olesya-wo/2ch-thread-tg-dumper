<?php

declare(strict_types=1);

require_once 'utils.php';
require_once 'tests.php';

test('Пустая строка', function () {
    $comment = '';
    $result = convert_comment($comment, true);
    _assert($result == '');
});
test('Заменяет всё на соответствующие тэги', function () {
    $comment = '<span class="s"><span class="spoiler">spoiler <span class="u">underline</span></span>strike';
    $result = convert_comment($comment, true);
    _assert($result == '<s><tg-spoiler>spoiler <u>underline</u></tg-spoiler>strike</s>');
});

end_tests();
