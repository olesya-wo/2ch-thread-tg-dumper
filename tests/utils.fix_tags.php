<?php

declare(strict_types=1);

require_once 'utils.php';
require_once 'tests.php';

test('Пустая строка', function () {
    $comment = '';
    $result = fix_tags($comment);
    _assert($result == '');
});
test('Расставляет тэги правильно', function () {
    $comment = "text <b><i>bold_italic</i></b>\r\n<b>bold <i>italic</i> bold</b>\r\n</b>text<del><b><i>bi</b></i><s>strike";
    $result = fix_tags($comment);
    _assert($result == "text <b><i>bold_italic</i></b>\r\n<b>bold <i>italic</i> bold</b>\r\ntext<del><b><i>bi</i></b><s>strike</s></del>");
});

end_tests();
