<?php

declare(strict_types=1);

chdir(__DIR__);

require_once 'settings.php';
require_once 'utils.php';

// Сразу выходим, если уже запущен экземпляр скрипта
if (file_exists(LOCK_FILE) && time() - filemtime(LOCK_FILE) < MAX_LOCK_TIME_SEC) {
    exit('Already running');
}

// При любом завершении скрипта (штатном или нет) должны удалить лок-файл
function on_exit() {
    unlink(LOCK_FILE);
}
register_shutdown_function('on_exit');
touch(LOCK_FILE);

// С какого номера идут новые посты
$last_posted_id = (int) file_get_contents(LAST_ID_FILE);
if ($last_posted_id < 1) {
    exit('No last post id');
}

// Скачиваем данные треда
$opts = [
    'http' => [
        'ignore_errors' => 1,
        'method' => 'GET',
    ],
    'ssl' => [
        'allow_self_signed' => true,
        'verify_peer' => false,
        'verify_peer_name' => false,
    ],
];
$content = file_get_contents(THREAD_JSON, false, stream_context_create($opts));
if (!$content) {
    exit('Getting thread content fail');
}
$data = null;
try {
    $data = json_decode($content);
} catch (Exception $e) {
    exit('Thread content parsing fail');
}
if (!$data->threads || !$data->threads[0]->posts) {
    exit('Thread content is invalid');
}

// И обрабатываем по каждому посту
foreach ($data->threads[0]->posts as $post) {
    $num = $post->num;
    // Уже постили
    if ($num <= $last_posted_id) {
        continue;
    }

    // Заголовок в виде #p123456 [url]
    $link = '#p'.$num.' <a href="'.THREAD_URL."#{$num}\">[url]</a>\r\n";

    // В Telegram отправляем только ссылки на медиа
    $files = '';
    foreach ($post->files as $file) {
        $files .= SITE_URL.$file->path."\r\n";
    }

    // Собираем все части
    $res = $link.$files.prepare_comment($post->comment);

    // Лимит на длину сообщения в Telegram значительно меньше, чем на борде
    if (mb_strlen($res) > MESSAGE_MAXLENGTH) {
        $res = mb_substr($res, 0, MESSAGE_MAXLENGTH)."\r\n[...]";
    }

    // Постим в Telegram
    $send_res = call_tg_api_method(
        'sendMessage',
        [
            'chat_id' => TG_CHANNEL_NAME,
            'text' => $res,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ]
    );
    if (!$send_res) {
        exit('call_tg_api_method fail');
    }

    // Запомнить в файле номер последнего поста
    file_put_contents(LAST_ID_FILE, $num);
    // Обновить время у лока
    touch(LOCK_FILE);
    // Иначе забанят
    sleep(4);
}
