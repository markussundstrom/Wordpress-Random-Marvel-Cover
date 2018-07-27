<?php
    /*
    Plugin Name: Random Marvel Cover
    Author: Markus Sundström
    License: GPL3
    Description: A Widget that displays a random cover from a Marvel comic
    Version: 0.1
    */

    Class MSuRMC extends WP_Widget {
        //Insert API keys for the Marvel API here
        private $_publickey = '';
        private $_privatekey = '';
        public function __construct() {
            parent::__construct('msu-rmc', 
                               __('Random Marvel Cover', 'text_domain'),
                                array('customize_selective_refresh'=>true,
                                ));
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
        private function createKey() {
            $ts = time();
            $hash = md5($ts . $this->_privatekey . $this->_publickey);
            $key = 'ts=' . $ts . '&apikey=' . $this->_publickey . 
                   '&hash=' . $hash;
            return $key;
        }
        
        //Fetches info about a random comic
        private function fetchRandomCover() {
            //First call to API is just to get the total number of comics
            $key = $this->createKey();
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
            $key = $this->createKey();
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
    add_action('widgets_init', 'msu_rmc_widget');

