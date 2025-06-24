<?php
/**
 * WP-CLI Command for DMG Read More plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Search for posts containing the DMG Read More block
 */
class DMG_Read_More_CLI {

    /**
     * Search for posts containing the dmg/read-more block within a date range
     *
     * ## OPTIONS
     *
     * [--date-before=<date>]
     * : Search for posts before this date. Format: YYYY-MM-DD
     *
     * [--date-after=<date>]
     * : Search for posts after this date. Format: YYYY-MM-DD
     *
     * ## EXAMPLES
     *
     *     # Search posts from the last 30 days
     *     $ wp dmg-read-more search
     *
     *     # Search posts between specific dates
     *     $ wp dmg-read-more search --date-after=2024-01-01 --date-before=2024-12-31
     *
     * @when after_wp_load
     */
    public function search($args, $assoc_args) {
        global $wpdb;

        // Parse date arguments
        $date_before = isset($assoc_args['date-before']) ? $assoc_args['date-before'] : date('Y-m-d', strtotime('+1 day'));
        $date_after = isset($assoc_args['date-after']) ? $assoc_args['date-after'] : date('Y-m-d', strtotime('-30 days'));

        // Validate dates
        if (!$this->validate_date($date_before) || !$this->validate_date($date_after)) {
            WP_CLI::error('Invalid date format. Please use YYYY-MM-DD format.');
            return;
        }

        // Convert dates to MySQL datetime format
        $date_before = $date_before . ' 23:59:59';
        $date_after = $date_after . ' 00:00:00';

        WP_CLI::log(sprintf('Searching for posts containing dmg/read-more block between %s and %s...', $date_after, $date_before));

        try {
            // Use direct SQL query for performance with large datasets
            // Search for the block comment in post_content
            $sql = $wpdb->prepare(
                "SELECT ID 
                FROM {$wpdb->posts} 
                WHERE post_type = 'post' 
                AND post_status = 'publish' 
                AND post_date >= %s 
                AND post_date <= %s 
                AND post_content LIKE %s
                ORDER BY ID",
                $date_after,
                $date_before,
                '%<!-- wp:dmg/read-more%'
            );

            // Execute query with chunking for memory efficiency
            $offset = 0;
            $limit = 1000;
            $found_posts = array();
            $total_found = 0;

            do {
                $chunked_sql = $sql . $wpdb->prepare(" LIMIT %d, %d", $offset, $limit);
                $results = $wpdb->get_col($chunked_sql);

                if (!empty($results)) {
                    foreach ($results as $post_id) {
                        // Double-check the post actually contains our block
                        $post_content = get_post_field('post_content', $post_id);
                        if (has_block('dmg/read-more', $post_content)) {
                            WP_CLI::log($post_id);
                            $found_posts[] = $post_id;
                            $total_found++;
                        }
                    }
                }

                $offset += $limit;

                // Break if we got fewer results than the limit
                if (count($results) < $limit) {
                    break;
                }

                // Add a small delay to prevent overwhelming the database
                if ($offset % 10000 === 0) {
                    WP_CLI::log(sprintf('Processed %d posts...', $offset));
                    usleep(100000); // 0.1 second delay
                }

            } while (!empty($results));

            if ($total_found === 0) {
                WP_CLI::log('No posts found containing the dmg/read-more block in the specified date range.');
            } else {
                WP_CLI::success(sprintf('Found %d posts containing the dmg/read-more block.', $total_found));
            }

        } catch (Exception $e) {
            WP_CLI::error('An error occurred while searching: ' . $e->getMessage());
        }
    }

    /**
     * Validate date format
     *
     * @param string $date Date string to validate
     * @return bool
     */
    private function validate_date($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}

// Register the command
WP_CLI::add_command('dmg-read-more', 'DMG_Read_More_CLI');