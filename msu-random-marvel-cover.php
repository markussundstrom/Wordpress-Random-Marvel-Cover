<?php
    /*
    Plugin Name: Random Marvel Cover
    Author: Markus Sundström
    License: GPL3
    Description: A Widget that displays a random cover from a Marvel comic
    Version: 0.1
    */

    Class MSuRMC extends WP_Widget {
        private $_publickey = '';
        private $_privatekey = '';
        public function __construct() {
            parent::__construct('msu-rmc', 
                               __('Random Marvel Cover', 'text_domain'),
                                array('customize_selective_refresh'=>true,
                                ));
            //Fetch APIkeys from settings
            $options = get_option('msu_rmc');
            $this->_publickey = $options['publickey'];
            $this->_privatekey = $options['privatekey'];
        }

        public function widget($args, $instance) {
            echo $before_widget;
            echo '<div class="widget_text wp_widget_plugin_box">';
            echo $before_title . 'Random Marvel Cover' . $after_title;
            $comic = $this->fetchRandomCover();
            if ($comic) {
                echo '<img src="' . $comic['coverurl'] . '" alt="' .
                     $comic['title'] . ' ' . $comic['issue'] . '">';
                echo '<br>' . $comic['title'] . ' #' . $comic['issue'];
                echo '<br><a href="' . $comic['info'] . '">More info</a>';
                echo '<br>' . $comic['attribution'];
            }

            echo '</div>';
            echo $after_widget;
        }

        //Creates a key value for API usage
        private function create_key() {
            $ts = time();
            $hash = md5($ts . $this->_privatekey . $this->_publickey);
            $key = 'ts=' . $ts . '&apikey=' . $this->_publickey . 
                   '&hash=' . $hash;
            return $key;
        }
        
        //Fetches info about a random comic
        private function fetchRandomCover() {
            //First call to API is just to get the total number of comics
            $key = $this->create_key();
            $url = 'http://gateway.marvel.com/v1/public/comics?limit=1&'.$key;
            $response = wp_remote_get($url);
            $code = wp_remote_retrieve_response_code($response);
            if ($code != '200') {
                echo '<pre>Error: ' . $code . '</pre>';
                return NULL;
            }
            $data = json_decode(wp_remote_retrieve_body($response), true);
            $total = $data['data']['total'];
            $offset = mt_rand(0, $total);

            //Make another call to API with randomized offset
            //to get a random comic
            $key = $this->create_key();
            $url .= '&offset=' . $offset; 
            $response = wp_remote_get($url);
            $code = wp_remote_retrieve_response_code($response);
            if ($code != '200') {
                echo '<pre>Error: ' . $code . '</pre>';
                return NULL;
            }
            $comic = array();
            $data = json_decode(wp_remote_retrieve_body($response), true);
            $comic['coverurl'] = $data['data']['results'][0]
                                 ['thumbnail']['path'] . '.' .
                                 $data['data']['results'][0]
                                 ['thumbnail']['extension'];
            $comic['attribution'] = $data['attributionHTML'];
            $comic['title'] = $data['data']['results'][0]['series']['name'];
            $comic['issue'] = $data['data']['results'][0]['issueNumber'];
            $comic['info'] = $data['data']['results'][0]['urls'][0]['url'] 
                             ?: 'http://www.marvel.com';
            return $comic;
        }
    }

    function msu_rmc_widget() {
        register_widget('MSuRMC');
    }

    function msu_rmc_add_page() {
        add_options_page('Random Marvel Cover', 'Random Marvel Cover', 
                         'manage_options', 'msu_rmc', 'msu_rmc_options_page');
    }
    
    //display admin options for plugin
    function msu_rmc_options_page() {
        echo '<div><h2>Random Marvel Cover</h2>' . 
             'Options relating to the Random Marvel Cover Plugin' .
             '<form action="options.php" method="post">';
        
        settings_fields('msu_rmc_keys');
        do_settings_sections('msu_rmc');
        submit_button();        
        echo '</form></div>';
    }
    
    //define settings for plugin
    function msu_rmc_admin_init() {
        add_settings_section('msu_rmc_keys', 'API keys',
                             'rmc_keys_text', 'msu_rmc');
        
        add_settings_field('privatekey', 'Private key', 'rmc_setting_field',
                           'msu_rmc', 'msu_rmc_keys', 
                           array('id'=> 'privatekey'));
        add_settings_field('publickey', 'Public Key', 'rmc_setting_field',
                           'msu_rmc', 'msu_rmc_keys', 
                           array('id'=> 'publickey'));                           
        register_setting('msu_rmc_keys', 'msu_rmc');         
//        register_setting('msu_rmc_keys', 'publickey');
    }

    //Outputs descriptive text for the settings section
    function rmc_keys_text() {
        echo 'Enter the public and private Marvel API keys';
    }

    //Outputs HTML for a setting field
    function rmc_setting_field($arg) {
        $options = get_option('msu_rmc');
        $id = $arg['id'];
        echo '<input id="' . $id . '" name="msu_rmc[' . 
             $id . ']" type="text" value="' . $options[$id] .
             '"/>';
    }

            
    add_action('widgets_init', 'msu_rmc_widget');
    add_action('admin_menu', 'msu_rmc_add_page');
    add_action('admin_init', 'msu_rmc_admin_init');

