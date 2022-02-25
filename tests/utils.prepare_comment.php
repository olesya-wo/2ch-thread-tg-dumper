<?php

declare(strict_types=1);

require_once 'utils.php';
require_once 'tests.php';

test('Пустая строка', function () {
    $comment = '';
    $result = prepare_comment($comment, true);
    _assert($result == '');
});
test('Удаляет непечатаемые символы', function () {
    $comment = "Ня\u{1F}\u{7F}\u{A0}\u{AD}\u{D0}!";
    $result = prepare_comment($comment, true);
    _assert($result == 'Ня!');
});
test('Заменяет переносы строк', function () {
    $comment = '1<br>2';
    $result = prepare_comment($comment, true);
    _assert($result == "1\r\n2");
});
test('Удаляет неподдерживаемые тэги', function () {
    $comment = '1<a href="https://test.ru/">[url]</a><b>2</b><img alt="" src="https://test.ru/">3';
    $result = prepare_comment($comment, true);
    _assert($result == '1[url]<b>2</b>3');
});
test('Reply', function () {
    $comment = '>>123 Text<br>>>1234Text';
    $result = prepare_comment($comment, true);
    _assert($result == ">>#p123 Text\r\n>>#p1234 Text");
});
test('Заменяет span на соответствующие тэги', function () {
    $comment = '<span class="s"><span class="spoiler">spoiler <span class="u">underline</span></span>strike</span>';
    $result = prepare_comment($comment, true);
    _assert($result == '<s><tg-spoiler>spoiler <u>underline</span></span>strike</span>');
});
test('Без full_support просто удаляет неподдерживаемые тэги', function () {
    $comment = '<span class="s"><span class="spoiler">spoiler <u>underline</u></span>strike</span>';
    $result = prepare_comment($comment, false);
    _assert($result == 'spoiler <u>underline</u>strike');
});

end_tests();
