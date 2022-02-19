<?php

declare(strict_types=1);

require_once 'utils.php';
require_once 'tests.php';

test('Пустая строка', function () {
    $comment = '';
    $result = prepare_comment($comment);
    _assert($result == '');
});
test('Удаляет непечатаемые символы', function () {
    $comment = "Ня\u{1F}\u{7F}\u{A0}\u{AD}\u{D0}!";
    $result = prepare_comment($comment);
    _assert($result == 'Ня!');
});
test('Заменяет переносы строк', function () {
    $comment = '1<br>2';
    $result = prepare_comment($comment);
    _assert($result == "1\r\n2");
});
test('Удаляет неподдерживаемые тэги', function () {
    $comment = '1<a href="https://test.ru/">[url]</a><b>2</b><img alt="" src="https://test.ru/">3';
    $result = prepare_comment($comment);
    _assert($result == '1[url]<b>2</b>3');
});
test('Reply', function () {
    $comment = '>>123 Text<br>>>1234Text';
    $result = prepare_comment($comment);
    _assert($result == ">>#p123 Text\r\n>>#p1234 Text");
});

end_tests();
