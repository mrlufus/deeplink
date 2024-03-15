<?php
/*
Plugin Name: App DeepLinker
Plugin URI: https://www.deephive.de/
Description: Opens URLs in App if installed, otherwise uses Browser as fallback.
Version: 1.1
Author: mrlufus
Author URI: https://mrlufus.de/
*/

if (!defined('YOURLS_ABSPATH')) die();

yourls_add_action('pre_redirect', 'start_deeplinker');

// Supported apps and their deeplink schemes
$supported_apps = array(
    'youtube' => 'vnd.youtube://watch?v=',
    'facebook' => 'fb://profile/',
    // Add more apps and their deeplink schemes here
);

// Deep Links a URL
function start_deeplinker($args){

    $url = $args[0];
    $code = $args[1];

    global $supported_apps;
 
    foreach ($supported_apps as $app_name => $app_scheme) {
        if (strpos($url, $app_name . '.com') !== false || strpos($url, $app_name . '.be') !== false){ 

            $url_parts = parse_url($url);

            if (empty($url_parts['path'])){
                yourls_redirect($url, 302);
                exit();
            } 

            $url_path = $url_parts['path'];

            if (!empty($url_parts['query'])){
                $url_path .= '?' . $url_parts['query'];
            }

            // Fetch the HTML content of the original URL
            $html_content = file_get_contents($url);
            if ($html_content === false) {
                yourls_redirect($url, 302); // Redirect to original URL if unable to fetch content
                exit();
            }

            // Extract the title from HTML content
            preg_match('/<title>(.*?)<\/title>/', $html_content, $title_matches);
            $title = isset($title_matches[1]) ? $title_matches[1] : 'Redirecting to ' . ucfirst($app_name); // Default title if not found

            // Extracting og:title, og:description, og:image from HTML content
            preg_match('/<meta\s+property="og:title"\s+content="(.*?)"/', $html_content, $og_title_matches);
            $og_title = isset($og_title_matches[1]) ? $og_title_matches[1] : $title;

            preg_match('/<meta\s+property="og:description"\s+content="(.*?)"/', $html_content, $og_description_matches);
            $og_description = isset($og_description_matches[1]) ? $og_description_matches[1] : '';

            preg_match('/<meta\s+property="og:image"\s+content="(.*?)"/', $html_content, $og_image_matches);
            $og_image = isset($og_image_matches[1]) ? $og_image_matches[1] : '';

            ?>
            <!DOCTYPE html>
            <html>
                <head>
                    <meta charset="utf-8">
                    <title><?php echo $title; ?></title>
                    <meta property="og:title" content="<?php echo $og_title; ?>">
                    <meta property="og:description" content="<?php echo $og_description; ?>">
                    <meta property="og:image" content="<?php echo $og_image; ?>">
                    <style>html {background:#000000;}</style>
                </head>
                <body>
                    <script type="text/javascript">
                        window.onload = function() {

                            var fallback = "<?= $url ?>";
                            var app = "<?= $app_scheme ?><?= $url_path ?>";

                            if (/Android|iPhone|iPad|iPod/i.test(navigator.userAgent)) {
                            window.location = app;
							// Sleep 100ms to prevent opening in Browser to early
							usleep( 100 * 1000 );
                            window.setTimeout(function() {
                                // couldn't open app, open fallback url
                                window.location = fallback;
                            }, 25);
                        } else {
                            // open url because device doesn't support youtube app
                            window.location = fallback;
                        }
                        function killPopup() {
                            window.removeEventListener('pagehide', killPopup);
                        }
                        window.addEventListener('pagehide', killPopup);
                    };
                    </script>
                </body>
            </html>
            <?php
            die();
        }
    }
}
?>
