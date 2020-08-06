<?php
chdir(__DIR__);
require 'settings.php';

function call_tg_api_method( $method, $params ): bool {
    $post_data = http_build_query( $params );
    $opts     = array(
        'http' => array(
            'ignore_errors' => 1,
            'method'        => 'POST',
            'header'        => "Content-Type: application/x-www-form-urlencoded\r\n" .
                'Content-Length: ' . strlen( $post_data ) . "\r\n",
            'content'       => $post_data
        ),
        'ssl' => array(
            'allow_self_signed' => true,
            'verify_peer'       => false,
            'verify_peer_name'  => false
        )
    );
    $content = file_get_contents( 'https://api.telegram.org/bot' . BOT_TOKEN . '/' . $method, false, stream_context_create( $opts ) );
    if ( !$content ) { return false; }
    $data = null;
    try {
        $data = json_decode( $content );
    }
    catch ( Exception $e ) {
        return false;
    }
    return $data && $data->{'ok'};
}

function on_exit() {
    unlink( LOCK_FILE );
}

if ( file_exists( LOCK_FILE ) && time() - filemtime( LOCK_FILE ) < MAX_LOCK_TIME_SEC ) { die( 'Already running' ); }

register_shutdown_function( 'on_exit' );
touch( LOCK_FILE );

$last_posted_id = ( int ) file_get_contents( LAST_ID_FILE );
if ( $last_posted_id < 1 ) { die( 'No last post id' ); }

$opts = array(
    'http' => array(
        'ignore_errors' => 1,
        'method'        => 'GET'
    ),
    'ssl' => array(
        'allow_self_signed' => true,
        'verify_peer'       => false,
        'verify_peer_name'  => false
    )
);
$content = file_get_contents( THREAD_JSON, false, stream_context_create( $opts ) );
if ( !$content ) { die( 'Getting thread content fail' ); }
$data = null;
try {
    $data = json_decode( $content );
}
catch ( Exception $e ) {
    die( 'Thread content parsing fail' );
}
if ( !$data->threads || !$data->threads[0]->posts ) { die( 'Thread content is invalid' ); }

foreach ( $data->threads[0]->posts as $post ) {
    $num = $post->num;
    if ( $num <= $last_posted_id ) { continue; }
    
    $link = '<a href="' . THREAD_URL . "#$num\">[$num]</a>\r\n";

    $files = '';
    foreach( $post->files as $file ) {
        $files .= SITE_URL . $file->path . "\r\n";
    }

    $comment = strip_tags( str_replace( '<br>', "\r\n", $post->comment ), '<b><i><u><s><a><code><pre>' );

    $res = $link . $files . $comment;

    if ( mb_strlen( $res ) > MESSAGE_MAXLENGTH ) {
        $res = mb_substr( $res, 0, MESSAGE_MAXLENGTH ) . "\r\n[...]";
    }

    $send_res = call_tg_api_method(
        'sendMessage',
        [
            'chat_id'                  => TG_CHANNEL_NAME,
            'text'                     => $res,
            'parse_mode'               => 'HTML',
            'disable_web_page_preview' => true
        ]
    );

    if ( !$send_res ) { die( 'call_tg_api_method fail' ); }

    file_put_contents( LAST_ID_FILE, $num );
    touch( LOCK_FILE );
    sleep( 4 );
}
