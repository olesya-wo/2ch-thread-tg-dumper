<?php

define( 'BOT_TOKEN', '' );
define( 'TG_CHANNEL_NAME', '@channel_name' );
define( 'MESSAGE_MAXLENGTH', 4000 );

define( 'SITE_URL', 'https://2ch.hk' );
define( 'BOARD', 'soc' );
define( 'THREAD_NUMBER', '4219284' );
define( 'THREAD_JSON', SITE_URL . '/' . BOARD . '/res/' . THREAD_NUMBER . '.json' );
define( 'THREAD_URL', SITE_URL . '/' . BOARD . '/res/' . THREAD_NUMBER . '.html' );

define( 'LAST_ID_FILE', './data/last_post_from_' . THREAD_NUMBER );

define( 'LOCK_FILE', './data/thread_' . THREAD_NUMBER . '_dump.lock' );
define( 'MAX_LOCK_TIME_SEC', 2000 );
