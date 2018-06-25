<?php
/*
Plugin Name: Social Share Count DB
Description: This plugin saves the number of shares on social networking services in the DataBase. Supported service is Twitter, Facebook, Google Plus, and Hatena Bookmark.
Version: 0.2
Author: Karakaram
Author URI: http://www.karakaram.com
License: GPL2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
*/

require_once dirname(__FILE__) . '/lib/SocialShareCountCrawler.php';

class SocialShareCountDB
{

    const DB_VERSION = '1';
    const DB_VERSION_OPTION = 'social-share-count-db-version';
    const CURRENT_PAGE_OPTION = 'social-share-count-current-page';
    const PER_PAGE = 10;
    const POST_META = 'social-share-count-db';
    const TWITTER = 'twitter';
    const FACEBOOK = 'facebook';
    const HATENA_BOOKMARK = 'hatena-bookmark';

    /**
     * @var SocialShareCountDB
     */
    private static $instance = null;

    public function __construct()
    {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_uninstall_hook(__FILE__, 'SocialShareCountDB::uninstall');
        add_action('save_post', array($this, 'savePost'));
        add_action('delete_post', array($this, 'deletePost'));
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new SocialShareCountDB();
        }
        return self::$instance;
    }

    public function activate()
    {
        /** @var WP_Post */
        global $post;

        if (self::DB_VERSION == get_option(self::DB_VERSION_OPTION)) {
            return;
        }

        $query = new WP_Query();
        $query->query(array('post_type' => 'post', 'posts_per_page' => -1, 'order' => 'ASC'));

        $count = array();
        $count[SocialShareCountDB::TWITTER] = 0;
        $count[SocialShareCountDB::FACEBOOK] = 0;
        $count[SocialShareCountDB::HATENA_BOOKMARK] = 0;
        while ($query->have_posts()) {
            $query->the_post();
            add_post_meta(
                $post->ID,
                self::POST_META,
                $count,
                true
            );
        }
        update_option(self::DB_VERSION_OPTION, self::DB_VERSION);
        update_option(self::CURRENT_PAGE_OPTION, 1);

    }

    public static function uninstall()
    {
        /** @var WP_Post $post */
        global $post;

        $query = new WP_Query();
        $query->query(array('post_type' => 'post', 'posts_per_page' => -1, 'order' => 'ASC'));

        while ($query->have_posts()) {
            $query->the_post();
            delete_post_meta($post->ID, self::POST_META);
        }
        delete_option(self::DB_VERSION_OPTION);
        delete_option(self::CURRENT_PAGE_OPTION);
    }

    public function savePost($postId)
    {
        $count = array();
        $count[SocialShareCountDB::TWITTER] = 0;
        $count[SocialShareCountDB::FACEBOOK] = 0;
        $count[SocialShareCountDB::HATENA_BOOKMARK] = 0;
        add_post_meta($postId, self::POST_META, $count, true);
    }

    public function deletePost($postId)
    {
        delete_post_meta($postId, self::POST_META);
    }

    public function requestSocialCount()
    {
        /** @var WP_Post $post */
        global $post;

        $current_page = get_option(self::CURRENT_PAGE_OPTION);
        $query = new WP_Query();
        $query->query(array('post_type' => 'post', 'paged' => $current_page, 'posts_per_page' => self::PER_PAGE, 'order' => 'ASC'));

        $crawler = new SocialShareCountCrawler();

        while ($query->have_posts()) {
            $query->the_post();

            $url = get_permalink($post->ID);
            $url_without_ssl = str_replace('https', 'http', $url);

            $count = array();
            $count[SocialShareCountDB::TWITTER] = $crawler->requestTwitter($url);
            $count[SocialShareCountDB::FACEBOOK] = $crawler->requestFacebook($url);
            $count[SocialShareCountDB::FACEBOOK] += $crawler->requestFacebook($url_without_ssl);
            $count[SocialShareCountDB::HATENA_BOOKMARK] = $crawler->requestHatenaBookmark($url);
            $count[SocialShareCountDB::HATENA_BOOKMARK] += $crawler->requestHatenaBookmark($url_without_ssl);

            update_post_meta($post->ID, SocialShareCountDB::POST_META, $count);

            usleep(5000000);
        }

        $query->query(array('post_type' => 'post', 'posts_per_page' => -1));
        $post_count = $query->found_posts;
        $page_count = floor($post_count / self::PER_PAGE);
        if ($post_count % self::PER_PAGE != 0) {
            $page_count++;
        }
        $current_page++;
        if ($current_page > $page_count) {
            $current_page = 1;
        }
        update_option(self::CURRENT_PAGE_OPTION, $current_page);
    }

}
$socialShareCountCache = SocialShareCountDB::getInstance();

function get_social_count($postId = null)
{
    if ($postId === null) {
        $postId = get_the_ID();
    }

    $count = get_post_meta($postId, SocialShareCountDB::POST_META, true);

    if ($count == null) {
        $count = array();
        $count[SocialShareCountDB::TWITTER] = 0;
        $count[SocialShareCountDB::FACEBOOK] = 0;
        $count[SocialShareCountDB::HATENA_BOOKMARK] = 0;
    }

    return $count;
}
