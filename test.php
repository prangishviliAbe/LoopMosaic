<?php
$dir = __DIR__;
while ( ! file_exists( $dir . '/wp-load.php' ) && dirname( $dir ) !== $dir ) {
    $dir = dirname( $dir );
}
if ( file_exists( $dir . '/wp-load.php' ) ) {
    require_once $dir . '/wp-load.php';
    global $wpdb;
    $keys = $wpdb->get_col("SELECT DISTINCT meta_key FROM {$wpdb->postmeta} WHERE meta_key LIKE '%redirect%' OR meta_key LIKE '%url%' OR meta_key LIKE '%link%'");
    print_r( $keys );

    // Find the student affairs post ID
    $post = get_page_by_title('სტუდენტურ საქმეთა დეპარტამენტი', OBJECT, 'post');
    if ($post) {
        echo "Post ID: {$post->ID}\n";
        $meta = get_post_meta($post->ID);
        print_r($meta);
    } else {
        // Just get a recent post's meta to see
        $posts = get_posts(['posts_per_page' => 5]);
        foreach ($posts as $p) {
            echo "Post ID: {$p->ID} - {$p->post_title}\n";
            $meta = get_post_meta($p->ID);
            foreach($meta as $k => $v) {
                if(strpos(strtolower($k), 'redirect') !== false || strpos(strtolower($k), 'url') !== false) {
                    echo "  $k => {$v[0]}\n";
                }
            }
        }
    }
} else {
    echo "WP Root not found.\n";
    // Check if there is an environment variable or common path
    $common_paths = [
        'C:\Users\Abe_P\Local Sites',
        'C:\xampp\htdocs'
    ];
    var_dump($common_paths);
}
