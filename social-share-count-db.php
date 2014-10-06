<?php
/*
Plugin Name: Social Share Count DB
Description: This plugin saves the number of shares on social networking services in the DataBase. Supported service is Twitter, Facebook, Google Plus, and Hatena Bookmark.
Version: 0.1
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
    const POST_META = 'social-share-count-db';
    const TWITTER = 'twitter';
    const FACEBOOK = 'facebook';
    const GOOGLE_PLUS = 'google-plus';
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
        $count[SocialShareCountDB::GOOGLE_PLUS] = 0;
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
    }

    public function savePost($postId)
    {
        $count = array();
        $count[SocialShareCountDB::TWITTER] = 0;
        $count[SocialShareCountDB::FACEBOOK] = 0;
        $count[SocialShareCountDB::GOOGLE_PLUS] = 0;
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

        $query = new WP_Query();
        $query->query(array('post_type' => 'post', 'posts_per_page' => -1, 'order' => 'ASC'));

        $crawler = new SocialShareCountCrawler();

        while ($query->have_posts()) {
            $query->the_post();

            $url = get_permalink($post->ID);

            $count = array();
            $count[SocialShareCountDB::TWITTER] = $crawler->requestTwitter($url);
            $count[SocialShareCountDB::FACEBOOK] = $crawler->requestFacebook($url);
            $count[SocialShareCountDB::GOOGLE_PLUS] = $crawler->requestGooglePlus($url);
            $count[SocialShareCountDB::HATENA_BOOKMARK] = $crawler->requestHatenaBookmark($url);

            update_post_meta($post->ID, SocialShareCountDB::POST_META, $count);

            usleep(500000);
        }
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
        $count[SocialShareCountDB::GOOGLE_PLUS] = 0;
        $count[SocialShareCountDB::HATENA_BOOKMARK] = 0;
    }

    return $count;
}