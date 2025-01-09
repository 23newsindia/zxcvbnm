<?php
/**
 * Handles the HTML processing pipeline
 */
class MACP_HTML_Processor {
    private $minifier;
    private $options;

    public function __construct() {
        $this->minifier = new MACP_HTML_Minifier();
        $this->options = [
            'minify_html' => get_option('macp_minify_html', 0),
            'minify_css' => get_option('macp_minify_css', 0),
            'minify_js' => get_option('macp_minify_js', 0)
        ];
    }

    public function process($html) {
    if (empty($html) || !get_option('macp_remove_unused_css', 0)) {
        return $html;
    }

    // Extract all CSS links
    preg_match_all('/<link[^>]*rel=["\']stylesheet["\'][^>]*href=["\']([^"\']+)["\'][^>]*>/i', $html, $matches);
    
    if (empty($matches[0])) {
        return $html;
    }

    $optimized_css = '';
    foreach ($matches[1] as $index => $css_url) {
        // Skip external fonts and excluded patterns
        if (strpos($css_url, 'fonts.googleapis.com') !== false) {
            continue;
        }

        // Get the optimized CSS from cache
        $cache_key = md5($css_url);
        $cache_file = WP_CONTENT_DIR . '/cache/macp/used-css/' . $cache_key . '.css';
        
        if (file_exists($cache_file)) {
            // Read optimized CSS
            $css_content = file_get_contents($cache_file);
            if ($css_content !== false) {
                // Replace the link tag with optimized CSS
                $html = str_replace(
                    $matches[0][$index],
                    "<style id=\"" . sanitize_title($css_url) . "\">\n" . $css_content . "\n</style>",
                    $html
                );
            }
        }
    }

    return $html;
}


private function process_css_file($url, $html) {
    $css_content = wp_remote_get($url);
    if (is_wp_error($css_content)) {
        return false;
    }
    
    $css_content = wp_remote_retrieve_body($css_content);
    if (empty($css_content)) {
        return false;
    }

    $optimizer = new MACP_CSS_Optimizer();
    return $optimizer->process_css($css_content, $html);
}
  
  }
