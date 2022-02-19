<?php

declare(strict_types=1);

// Для отправки данных в Telegram
function call_tg_api_method($method, $params): bool {
    $post_data = http_build_query($params);
    $opts = [
        'http' => [
            'ignore_errors' => 1,
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                'Content-Length: '.mb_strlen($post_data)."\r\n",
            'content' => $post_data,
        ],
        'ssl' => [
            'allow_self_signed' => true,
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ];
    $content = file_get_contents('https://api.telegram.org/bot'.BOT_TOKEN.'/'.$method, false, stream_context_create($opts));
    if (!$content) {
        return false;
    }

    $data = null;
    try {
        $data = json_decode($content);
    } catch (Exception $e) {
        return false;
    }
    return $data && $data->{'ok'};
}

// Не все вариации тэгов с борды валидны для Telegram, фиксим это
function fix_tags(string $inp): string {
    // Тэгов нет - сразу выходим
    if (!preg_match('/<\/?([a-zA-Z\d]+)>/', $inp)) {
        return $inp;
    }
    $stack = [];
    $res = '';
    for ($i = 0; $i < mb_strlen($inp); $i++) {
        // Не тэг - просто добавляем в результат
        if ($inp[$i] != '<') {
            $res .= $inp[$i];
            continue;
        }
        $s = mb_substr($inp, $i);
        // Открывающие тэги просто помещаем в стек
        if (preg_match('/^<([a-zA-Z\d]+)>/', $s, $matches)) {
            $stack[] = $matches[1];
            $res .= $inp[$i];
            continue;
        }
        // Закрывающие
        if (preg_match('/^<\/([a-zA-Z\d]+)>/', $s, $matches)) {
            // Нет парного открывающего - пропускаем
            if (!in_array($matches[1], $stack)) {
                $i += mb_strlen($matches[1]) + 2;
                continue;
            }
            $last = end($stack);
            if ($last == $matches[1]) {
                // Нормально закрыт последний открытый
                array_pop($stack);
                $res .= $inp[$i];
                continue;
            }
            // Выталкиваем все тэги до нужного и закрываем их
            while (count($stack) > 0) {
                $last = array_pop($stack);
                if ($last == $matches[1]) {
                    break;
                }
                $res .= "</{$last}>";
            }
            $res .= $inp[$i];
        }
    }
    while (count($stack) > 0) {
        $last = array_pop($stack);
        $res .= "</{$last}>";
    }
    return $res;
}

// Подготовить тело поста к отправке в Telegram
function prepare_comment(string $comment): string {
    // Удаляем непечатаемые символы
    $comment = preg_replace('/[\x00-\x1F\x7F\xA0\xAD\xD0]/u', '', $comment);
    // Telegram использует другой перенос строк
    $comment = str_replace('<br>', "\r\n", $comment);
    // Оставляем только известные тэги, которые может пережевать наш fix_tags
    $comment = strip_tags($comment, '<b><strong><i><em><u><ins><s><strike><del><code><pre>');
    // Ссылки для reply хэш-тэгов
    $comment = preg_replace('/>>(\d+) ?/', '>>#p\1 ', $comment);

    return fix_tags($comment);
}
