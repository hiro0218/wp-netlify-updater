<?php
/*
Plugin Name: Wp Netlify Updater
Plugin URI: https://github.com/hiro0218/wp-netlify-updater
Description: WordPress plugins Netlify build hook when updating posts
Version: 1.0.0
Original Author: yahsan2
Original Author URI: https://github.com/yahsan2
Author: hiro
Author URI: https://github.com/hiro0218/
*/
class WpNetlifyUpdater
{
    function __construct()
    {
        $this->version = '1.0.0';
        $this->name = 'Wp Netlify Updater';
        $this->slug = 'wp-netlify-updater';
        $this->prefix = 'wpnu_';

        $this->set_options();

        if (is_admin()) {
            add_action('transition_post_status', [$this, 'is_published_now'], 10, 3);
            add_action('wp_insert_post', [$this, 'do_something_on_published'], 100, 2);
            add_action('admin_menu', array($this, 'add_menu'));
        }
    }

    function is_published_now($new_status, $old_status, $post)
    {
        global $is_publiched;
        if (
            ($old_status == 'auto-draft' ||
                $old_status == 'draft' ||
                $old_status == 'pending' ||
                $old_status == 'future') &&
            $new_status == 'publish' &&
            $post->post_type == 'post'
        ) {
            if (empty($is_publiched) && $old_status == 'future') {
                $this->netlify_webhooks();
            } else {
                $is_publiched = true;
            }
        } else {
            if (!$is_publiched) {
                $is_publiched = false;
            }
        }
    }

    function do_something_on_published($post_ID, $post)
    {
        global $is_publiched;
        if ($is_publiched) {
            $this->netlify_webhooks();
            $is_publiched = false;
        }
    }

    function netlify_webhooks()
    {
        if (isset($this->webhook_url) && $this->webhook_url) {
            $url = $this->webhook_url;
            $data = array();
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($curl);
            curl_close($curl);
        }
    }

    function add_menu()
    {
        add_submenu_page(
            'options-general.php',
            $this->name,
            $this->name,
            'level_8',
            __FILE__,
            array($this, 'option_page'),
            ''
        );
    }

    function set_options()
    {
        $opt = get_option($this->prefix . 'option');
        if (isset($opt)) {
            $this->webhook_url = $opt['webhook_url'];
        } else {
            $this->webhook_url = null;
        }
    }

    function option_page()
    {
        if (isset($_POST[$this->prefix . 'option'])): ?>
      <?php
      check_admin_referer($this->slug);
      $opt = $_POST[$this->prefix . 'option'];
      update_option($this->prefix . 'option', $opt);
      ?>
      <div class="updated fade"><p><strong><?php _e('Options saved.'); ?></strong></p></div>
      <?php endif; ?>
      <div class="wrap">
        <h2><?php echo $this->name; ?></h2>
        <form action="" method="post">
            <?php
            wp_nonce_field($this->slug);
            $this->set_options();
            ?>
            <table class="form-table">
                <tr valign="top">
                    <td scope="row">
                      <label for="input_url">Netlify Incoming Webhooks</label>
                      <p>Docs: <a href="https://www.netlify.com/docs/webhooks/" target="_blank">https://www.netlify.com/docs/webhooks/</a></p>
                    </td>
                    <td>
                      <p>URL:</p>
                      <input name="<?php echo $this->prefix; ?>option[webhook_url]" type="text" id="input_url" value="<?php echo $this->webhook_url; ?>" class="regular-text" placeholder="https://api.netlify.com/build_hooks/xxxxxxxxxxxxxxxxxxxxxxxx" />
                    </td>
                </tr>
            </table>
            <p class="submit"><input type="submit" name="Submit" class="button-primary" value="変更を保存" /></p>
        </form>
      </div>
      <!-- /.wrap -->
      <?php
    }
}

global $wp_netlify_updater;
$wp_netlify_updater = new WpNetlifyUpdater();
