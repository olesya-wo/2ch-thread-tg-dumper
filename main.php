<?php
chdir(__DIR__);

$token             = ''; // telegram-bot token
$channel_name      = '@channel_name';
$site_url          = 'https://2ch.hk';
$thread_number     = '4219284';
$thread_url        =  "$site_url/soc/res/$thread_number.json";
$last_id_file      = 'last_post.txt';
$message_maxlength = 4000;

function call_tg_api_method( $method, $params ): bool {
    global $token;
    $postdata = http_build_query( $params );
    $opts     = array(
        'http' => array(
            'ignore_errors' => 1,
            'method'        => 'POST',
            'header'        => "Content-Type: application/x-www-form-urlencoded\r\n" .
                'Content-Length: ' . strlen( $postdata ) . "\r\n",
            'content'       => $postdata
        ),
        'ssl' => array(
            'allow_self_signed' => true,
            'verify_peer'       => false,
            'verify_peer_name'  => false
        )
    );
    $content = file_get_contents( 'https://api.telegram.org/bot' . $token . '/' . $method, false, stream_context_create( $opts ) );
    if ( !$content ) { return false; }
    $data = json_decode( $content );
    return $data and $data->{'ok'};
}

$last_posted_id = intval( file_get_contents( $last_id_file ) );
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
$content = file_get_contents( $thread_url, false, stream_context_create( $opts ) );
if ( !$content ) { die( 'Thread content is empty' ); }
$data = json_decode( $content );
if ( !$data ) { die( 'Thread content parsing fail' ); }
if ( !$data->threads or !$data->threads[0]->posts ) { die( 'Thread content is invalid' ); }

foreach ( $data->threads[0]->posts as $post ) {
    $num = $post->num;
    if ( $num <= $last_posted_id ) { continue; }
    
    $link = "<a href=\"$site_url/soc/res/$thread_number.html#$num\">[$num]</a>\r\n";

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
    sleep( 5 );
}
