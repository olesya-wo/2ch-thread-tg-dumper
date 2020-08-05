<?php
chdir(__DIR__);
require 'settings.php';

function call_tg_api_method( $method, $params ): bool {
    global $token;
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
    $content = file_get_contents( 'https://api.telegram.org/bot' . $token . '/' . $method, false, stream_context_create( $opts ) );
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
    global $lock_file;
    unlink( $lock_file );
}

if ( file_exists( $lock_file ) && time() - filemtime( $lock_file ) < $max_lock_lifetime ) { die( 'Already running' ); }

register_shutdown_function( 'on_exit' );
touch( $lock_file );

$last_posted_id = ( int ) file_get_contents( $last_id_file );
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
$content = file_get_contents( $thread_json, false, stream_context_create( $opts ) );
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
    
    $link = "<a href=\"$thread_url#$num\">[$num]</a>\r\n";

    $files = '';
    foreach( $post->files as $file ) {
        $files .= $site_url . $file->path . "\r\n";
    }

    $comment = strip_tags( str_replace( '<br>', "\r\n", $post->comment ), '<b><i><u><s><a><code><pre>' );

    $res = $link . $files . $comment;

    if ( mb_strlen( $res ) > $message_maxlength ) {
        $res = mb_substr( $res, 0, $message_maxlength ) . "\r\n[...]";
    }
    
    $send_res = call_tg_api_method(
        'sendMessage',
        [
            'chat_id'                  => $channel_name,
            'text'                     => $res,
            'parse_mode'               => 'HTML',
            'disable_web_page_preview' => true
        ]
    );

    if ( !$send_res ) { die( 'call_tg_api_method fail' ); }

    file_put_contents( $last_id_file, $num );
    touch( $lock_file );
    sleep( 4 );
}
