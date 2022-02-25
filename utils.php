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

// Проверяет, есть ли в стеке парный открывающий тэг для указанного закрывающего тэга
function tag_in_array(string $tag, array $stack): bool {
    if ($tag == 'span') {
        return in_array('u', $stack) || in_array('s', $stack) || in_array('tg-spoiler', $stack);
    }
    return in_array($tag, $stack);
}

// Проверяет парность открытого и закрытого тэга
function is_pair(string $open, string $close): bool {
    if ($close == 'span') {
        return $open == 'u' || $open == 's' || $open == 'tg-spoiler';
    }
    return $open == $close;
}

// Не все вариации тэгов с борды валидны для Telegram, фиксим это
function fix_tags(string $inp): string {
    // Тэгов нет - сразу выходим
    if (!preg_match('/<\/?[a-zA-Z\d-]+[ >]/', $inp)) {
        return $inp;
    }
    $stack = [];
    $res = '';
    for ($i = 0; $i < mb_strlen($inp); $i++) {
        $ch = mb_substr($inp, $i, 1);
        // Не тэг - просто добавляем в результат
        if ($ch != '<') {
            $res .= $ch;
            continue;
        }
        $s = mb_substr($inp, $i);
        // Открывающие тэги просто помещаем в стек
        if (preg_match('/^<([a-zA-Z\d-]+)[ >]/', $s, $matches)) {
            $stack[] = $matches[1];
            $res .= $ch;
            continue;
        }
        // Закрывающие
        if (preg_match('/^<\/([a-zA-Z\d-]+)>/', $s, $matches)) {
            $cl = $matches[1];
            // Нет парного открывающего - пропускаем
            if (!tag_in_array($cl, $stack)) {
                $i += mb_strlen($cl) + 2;
                continue;
            }
            $last = end($stack);
            if (is_pair($last, $cl)) {
                // Нормально закрыт последний открытый
                array_pop($stack);
                $res .= "</{$last}>";
                // Пропускаем закрывающий
                $i += mb_strlen($cl) + 2;
                continue;
            }
            // Выталкиваем все тэги до нужного и закрываем их
            while (count($stack) > 0) {
                $last = array_pop($stack);
                if (is_pair($last, $cl)) {
                    break;
                }
                $res .= "</{$last}>";
            }
            $res .= "</{$last}>";
            // Пропускаем закрывающий
            $i += mb_strlen($cl) + 2;
        }
    }
    while (count($stack) > 0) {
        $last = array_pop($stack);
        $res .= "</{$last}>";
    }
    return $res;
}

// Подготовить текст перед исправлением тэгов
function prepare_comment(string $comment, bool $full_support): string {
    // Удаляем непечатаемые символы
    $comment = preg_replace('/[\x00-\x1F\x7F\xA0\xAD\xD0]/u', '', $comment);
    // Telegram использует другой перенос строк
    $comment = str_replace('<br>', "\r\n", $comment);
    if ($full_support) {
        // Меняем обозначение спойлера для Telegram
        $comment = str_replace('<span class="spoiler">', '<tg-spoiler>', $comment);
        // Классы s, u в span на борде используются для strikeout и underline
        $comment = str_replace('<span class="u">', '<u>', $comment);
        $comment = str_replace('<span class="s">', '<s>', $comment);
        // Оставляем только известные тэги, которые может пережевать fix_tags
        $comment = strip_tags($comment, '<b><strong><i><em><u><ins><s><strike><del><code><pre><tg-spoiler><span>');
    } else {
        // Оставляем только известные тэги, которые может пережевать Telegram
        $comment = strip_tags($comment, '<b><strong><i><em><u><ins><s><strike><del><code><pre>');
    }
    // Ссылки для reply хэш-тэгов
    return preg_replace('/>>(\d+) ?/', '>>#p\1 ', $comment);
}

// Конвертировать тело поста для отправки в Telegram
function convert_comment(string $comment, bool $full_support): string {
    $comment = prepare_comment($comment, $full_support);
    if ($full_support) {
        $comment = fix_tags($comment);
        $comment = strip_tags($comment, '<b><strong><i><em><u><ins><s><strike><del><code><pre><tg-spoiler>');
    }

    return $comment;
}
