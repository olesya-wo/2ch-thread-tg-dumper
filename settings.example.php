<?php
$token             = ''; // telegram-bot token
$channel_name      = '@channel_name';
$site_url          = 'https://2ch.hk';
$thread_number     = '4219284';
$thread_json       = "{$site_url}/soc/res/{$thread_number}.json";
$thread_url        = "{$site_url}/soc/res/{$thread_number}.html";
$last_id_file      = "./data/last_post_for_{$thread_number}.txt";
$lock_file         = "./data/thread_{$thread_number}_dump.lock";
$message_maxlength = 4000;
$max_lock_lifetime = 60 * 30; // 30m
