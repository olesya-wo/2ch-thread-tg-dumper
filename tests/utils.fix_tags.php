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
test('Расставляет тэги правильно для юникода', function () {
    $comment = "<strong>ГОДЫ ИДУТ МОИ, БАБОНЬКИ, ТОРОПИТЕСЬ!!!\r\nУСТАЛ Я ИСКАТЬ СВОЮ ПРИНЦЕССУ УЖЕ</strong>";
    $result = fix_tags($comment);
    _assert($result == "<strong>ГОДЫ ИДУТ МОИ, БАБОНЬКИ, ТОРОПИТЕСЬ!!!\r\nУСТАЛ Я ИСКАТЬ СВОЮ ПРИНЦЕССУ УЖЕ</strong>");
});
test('Расставляет тэги правильно для тэгов с параметрами', function () {
    $comment = 'text <span class="u">длительную <span class="spoiler">в последнее время';
    $result = fix_tags($comment);
    _assert($result == 'text <span class="u">длительную <span class="spoiler">в последнее время</span></span>');
});
test('Расставляет тэги правильно для тэгов с нестандартным закрывающим тэгом', function () {
    $comment = '<s><tg-spoiler>spoiler <u>underline</span></span>strike</span>';
    $result = fix_tags($comment);
    _assert($result == '<s><tg-spoiler>spoiler <u>underline</u></tg-spoiler>strike</s>');
});

end_tests();
