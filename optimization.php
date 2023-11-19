<?php 
/*
Plugin Name: WPCWV Optimization
Plugin URI: https://www.wordpress.org/
Description: Fix Core Web Vital issues on site
Version: 1.0.0
Author: WPCWV
Author URI: https://www.wordpress.org/
License: GPLv3 or later
*/

add_action('wp','wpcwv_remove_emoji');
function wpcwv_remove_emoji(){
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 ); 
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' ); 
	remove_action( 'wp_print_styles', 'print_emoji_styles' ); 
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
}
wpcwv_prime_time('parse_start');
add_filter( 'admin_init' , 'register_fields' );

function register_fields() {
    register_setting( 'general', 'turn_on_optimization', 'esc_attr' );
    add_settings_field('turn_on_optimization', '<label for="turn_on_optimization">'.__('Turn on Optimization' , 'turn_on_optimization' ).'</label>' , 'fields_html', 'general' );
}
function fields_html() {
    $value = get_option( 'turn_on_optimization', '' );
    echo '<input type="checkbox" id="turn_on_optimization" name="turn_on_optimization" value="1" ' . (!empty($value) ? 'checked="checked"' : '') . '" />';
}
global $exclude_optimization,$optimize_image_array,$sitename, $image_home_url,$home_url,$full_url,$full_url_without_param, $secure,$additional_img,$exclude_lazyload,$exclude_css,$full_url_array,$main_css_url, $current_user,$lazy_load_js,$document_root,$fonts_api_links,$lazyload_inner_js,$lazyload_inner_ads_js,$lazyload_inner_ads_js_arr,$css_ext,$js_ext;
$optimize_image_array = array();
$secure =  (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$home_url = $secure.$_SERVER['HTTP_HOST'];
$image_home_url = $secure.$_SERVER['HTTP_HOST'];
$sitename = 'home';
$document_root = $_SERVER['DOCUMENT_ROOT'];
$full_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$full_url_array = explode('?',$full_url);
$full_url_without_param = $full_url_array[0];
$exclude_lazyload = array('alt'=>'Logo Name','class'=>'no-lazy'); // Add Logo Alt here to ensure it doesn't get lazy loaded
$exclude_optimization = array();
$useragent=$_SERVER['HTTP_USER_AGENT'];


$css_ext = '.css';
$js_ext = '.js';
$exclude_css = array('/themes/xxxxx/style.css'); // This array consists of all the CSS files that must be loaded before HTML, like Theme, Custom, etc. 

$exclude_inner_js=array('gtag');
$additional_img = array();
$lazyload_inner_js = array();
$lazyload_inner_ads_js = array();//key=>value
$main_css_url = array();
$lazy_load_js = array();


function wpcwv_isexternal($url) {
  $components = parse_url($url);
  return !empty($components['host']) && strcasecmp($components['host'], $_SERVER['HTTP_HOST']) && strpos($url,'xxxx') === false; // Replace xxxx with site name
}

function wpcwv_compress( $minify )
{
	$minify = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $minify );
	$minify = str_replace( array("\r\n", "\r", "\n", "\t",'  ','    ', '    '), ' ', $minify );
	return $minify;
}
function endswith($string, $test) {
    $strlen = strlen($string);
    $testlen = strlen($test);
    if ($testlen > $strlen) return false;
    return substr_compare($string, $test, $strlen - $testlen, $testlen) === 0;
}
function create_blank_file($path){
    $file = fopen($path,'c');
    fwrite($file,'//Silence is golden');
    fclose($file);
}
function wpcwv_prime_time($text){
    if(empty($_REQUEST['rest'])){
        return;
    }
    global $starttime;
    if(empty($starttime)){
        $starttime = microtime(true);
    }else{
        $endtime = microtime(true);
        $duration = $endtime-$starttime;
        $hours = (int)($duration/60/60);
        $minutes = (int)($duration/60)-$hours*60;
        $seconds = (int)$duration-$hours*60*60-$minutes*60;
        echo $duration.$text.'<br>';
    }
}

function str_replace_first($from, $to, $content)
{
    $from = '/'.preg_quote($from, '/').'/';
    return preg_replace($from, $to, $content, 1);
}
function wpcwv_parse_link($tag,$link){
    $xmlDoc = new DOMDocument();
    libxml_use_internal_errors(true);
    $xmlDoc->loadHTML($link);

    $tag_html = $xmlDoc->getElementsByTagName($tag);
    $link_arr = array();
    foreach ($tag_html[0]->attributes as $attr) {
        $link_arr[$attr->nodeName] = $attr->nodeValue;
    }
    return $link_arr;
}
function parse_script($tag,$link){
    $data_exists = strpos($link,'>');
    if(!empty($data_exists)){
        $end_tag_pointer = strpos($link,'</script>',$data_exists);
        $link_arr = substr($link, $data_exists+1, $end_tag_pointer-$data_exists-1);
    }
    return $link_arr;
}
function implode_link_array($tag,$array){
    $link = '<'.$tag.' ';
    foreach($array as $key => $arr){
        $link .= $key.'="'.$arr.'" ';
    }
    $link .= '>';
    return $link;
}
function implode_script_array($tag,$array){
    $link = '<script ';
    foreach($array as $key => $arr){
        $link .= $key.'="'.$arr.'" ';
    }
    $link .= '></script>';
    return $link;
}
function str_replace_set($str,$rep){
    global $str_replace_str_array, $str_replace_rep_array;
    $str_replace_str_array[] = $str;
    $str_replace_rep_array[] = $rep;
}
function str_replace_bulk($html){
    global $str_replace_str_array, $str_replace_rep_array;
    $html = str_replace($str_replace_str_array,$str_replace_rep_array,$html);
    return $html;
}
function start_site_optimization(){
    ob_start();
}
function get_site_optimized($html){
    global $sitename, $image_home_url,$home_url,$full_url,$full_url_without_param, $secure,$additional_img,$exclude_lazyload,$exclude_css,$full_url_array,$main_css_url, $current_user,$lazy_load_js,$document_root,$fonts_api_links,$lazyload_inner_js,$css_ext,$js_ext,$exclude_inner_js,$exclude_optimization,$lazyload_inner_ads_js,$lazyload_inner_ads_js_arr;
    if(!empty($_REQUEST['orgurl'])){
        return $html;
    }
    $value = get_option( 'turn_on_optimization');
    if(empty($value) && empty($_REQUEST['tester'])){
         return $html;
    }
    if(is_admin() || $GLOBALS['pagenow'] === 'wp-login.php'){
        return $html;
    }
    
    if(is_404() || (!empty($current_user) && current_user_can('edit_others_pages')) ){//
        return $html;
    }
    $current_url = !empty($full_url_without_param) ? trim($full_url_without_param,'/') : $sitename;
    $url_array = explode('/',trim(str_replace($home_url,'',$full_url),'/'));
    $sanitize_url = $current_url;
    $display_css = false;
    $full_cache_path = $document_root.'/wpcwv-cache';
    $encoded_url = '';
    if(!empty($url_array)){
        if(!file_exists($full_cache_path)){
            mkdir($full_cache_path, 0777, true);
            create_blank_file($full_cache_path.'/index.php');
        }
        for($i=0; $i < count($url_array); $i++){
            $full_cache_path .= '/'.base64_encode($url_array[$i]);
            $encoded_url .= '/'.base64_encode($url_array[$i]);
            if(!file_exists($full_cache_path)){
                mkdir($full_cache_path, 0777, true);
                create_blank_file($full_cache_path.'/index.php');
            }
        }
    }

    $all_js= '';
    $all_js1= '';
	$all_css='';
    $all_js_html = '';
    $uri_parts = explode('?', trim(str_replace($home_url,'',$full_url),'/'), 2);
    $current_url = $full_url_without_param;
    wpcwv_prime_time('parse_html');
    $all_links = wpcwv_setAllLinks($html);
    wpcwv_prime_time('parse_html_done');
    $script_links = $all_links['script'];
    $is_js_file_updated = 0;
  
    foreach($script_links as $script){
        $script_text='';
        $script_obj = array();
        $script_obj = wpcwv_parse_link('script',$script);
        if(!array_key_exists('src',$script_obj)){
            $script_text = parse_script('<script',$script);
        }

        if(!empty($script_obj['type']) && strtolower($script_obj['type']) != 'text/javascript'){
            continue;
        }
        
        if(!empty($script_obj['src'])){
            $url_array = parse_url($script_obj['src']);
            if(!wpcwv_isexternal($script_obj['src']) || file_exists($document_root.$url_array['path'])){
                $script_obj['src'] = $home_url.$url_array['path'];
            }
            $val = $script_obj['src'];
            
            if(!empty($val) && !wpcwv_isexternal($val) && strpos($val, '.js')){
                $src = explode('?',$val);
                $path = parse_url($src[0], PHP_URL_PATH);
                $filename = $document_root . $path;
                $src_array = explode('/',$src[0]);
                $src_array = array_reverse($src_array);
                unset($src_array[0]);
                $all_js .= str_replace('sourceMappingURL=','sourceMappingURL='.implode('/',$src_array),file_get_contents($filename).";\n");
                str_replace_set($script,'',$html);
            } else {
                //echo 'rocket-'.$script_obj['src'].'<br>';
                if((strpos($val,'stripe') > 0 || strpos($val,'google') > 0) && strpos($val,'recaptcha/api.js') === false && strpos($val,'googletagmanager') === false && strpos($val,'googletagservices') === false ){
                    $script_obj['defer'] = "defer";
                    if(strpos($val,'maps.googleapis') === false){
                        $js_src = explode('?',$val);
                        $script_obj['src'] = $js_src[0];
                    }
                    str_replace_set($script,implode_script_array('<script',$script_obj),$html);
                } else {
                    $lazy_load_js[] = $val;
                    str_replace_set($script,'',$html);
                }
            }


        } else {
          $inner_js = $script_text;
          $lazy_loadjs = 0;
          $exclude_js_bool = 0;
            if(!empty($exclude_inner_js)){
              foreach($exclude_inner_js as $js){
                  if(strpos($inner_js,$js) !== false){
                     $exclude_js_bool=1;
                  }
              }
          }
          if(!empty($exclude_js_bool)){
              continue;
          }
          if(!empty($lazyload_inner_js)){
              foreach($lazyload_inner_js as $key => $js){
                  if(strpos($script,$key)){
                      $lazyload_inner_js[$key] .= $inner_js.";\n";
                      $lazy_loadjs = 1;
                  }
              }
          }
          if(!empty($lazyload_inner_ads_js)){
              foreach($lazyload_inner_ads_js as $key => $js){
                  if(strpos($script,$key)){
                      $lazyload_inner_ads_js_arr[] = $inner_js;
                      $lazy_loadjs = 1;
                  }
              }
          }

          if(!$lazy_loadjs){
            $all_js .= $inner_js.";\n";
          }
          str_replace_set($script,'',$html);
        }
    }
    $js_exists = 0;
    remove_oldfiles($full_cache_path);

    $js_exists = rand(1,1000);
    $file = fopen($full_cache_path.'/all-js'.$js_exists.$js_ext,'c');
    fwrite($file,$all_js);
    fclose($file);

    $iframe_links = $all_links['iframe'];
    foreach($iframe_links as $img){
        $img_obj = wpcwv_parse_link('iframe',$img);
        $img_obj['data-src'] = $img_obj['src'];
        $img_obj['src'] = '';
        $img_obj['data-class'] = 'LazyLoad';
        str_replace_set($img,implode_link_array('iframe',$img_obj),$html);
    }
    $img_links = $all_links['img'];

    foreach($img_links as $img){
        $img_obj = wpcwv_parse_link('img',$img);
        $val = $img_obj['src'];
        if(strpos($img_obj['class'],'no-lazy') !== false || !empty($img_obj['data-class'])){
            continue;
        }
	
        $exclude_lazyload_img=0;
        if(!empty($exclude_lazyload)){
            foreach($exclude_lazyload as $key => $ex_img){
                if(strpos($img_obj[$key], $ex_img) !== false){
                    $exclude_lazyload_img = 1;
                }
            }
        }
        $exclude_optimization_img=1;
        if(!empty($exclude_optimization)){
            foreach($exclude_optimization as $ex_img){
                if(strpos($img_obj['class'], $ex_img) !== false){
                    $exclude_optimization_img = 1;
                }
            }
        }
        if(!empty($val)){
            $url_arr = parse_url($val);
            $img_obj['src'] = $image_home_url.'/wp-content/themes/xxxx/images/blank.png'; //Add blank image path
            if(wpcwv_isexternal($val) && !file_exists($document_root.$url_arr['path'])){
                if($exclude_lazyload_img){
                   $img_obj['src'] = $val;
                }else{
                    $img_obj['data-src'] = $val;
                }
            }else{
                if(!$exclude_optimization_img){
                    $cache_img = get_image_cache_path($home_url.$url_arr['path'],$img_obj['title'],$img_obj['alt'] );
                }else{
                    $cache_img = 'notfound';
                }
                if($exclude_lazyload_img){
                    if(file_exists($document_root.$cache_img)){
                        $img_obj['src'] = $image_home_url.$cache_img;
                    }
                    else
                        $img_obj['src'] = $image_home_url.$url_arr['path'];
                }else{
                    if(file_exists($document_root.$cache_img) ){
                        if(!empty($img_obj['srcset'])){
                            $srcset = optimize_srcset($img_obj['srcset']);
                            $img_obj['data-srcset'] = $srcset;
                            $img_obj['srcset'] = $image_home_url.'/wp-content/themes/xxxx/images/blank.png 500w, '.$image_home_url.'/wp-content/themes/xxxx/images/blank.png 1000w ';
                        }
                        $img_obj['data-src'] = $image_home_url.$cache_img;
                        $img_obj['data-opt'] = 'yes';
                    }else{
                        if(!empty($img_obj['srcset'])){
                            $img_obj['data-srcset'] = str_replace($home_url,$image_home_url,$img_obj['srcset']);
                            $img_obj['srcset'] = $image_home_url.'/wp-content/themes/xxxx/images/blank.png 500w, '.$image_home_url.'/wp-content/themes/xxxx/images/blank.png 1000w ';
                        }
                        $img_obj['data-src'] = $image_home_url.$url_arr['path'];
                    }
                }
            }
            $img_obj['data-class'] = 'LazyLoad';
            str_replace_set($img,implode_link_array('img',$img_obj),$html);

        }

    }
   
    wpcwv_prime_time('defer_images_done');
    $all_css1 = '';

    $css_links = $all_links['link'];
    $fonts_api_links = array();
    $i= 1;
    
    foreach($css_links as $css){
        $css_obj = wpcwv_parse_link('link',$css);
        
        if($css_obj['rel'] == 'stylesheet'){
            $media = '';
            if(!empty($css_obj['media']) && $css_obj['media'] != 'all' && $css_obj['media'] != 'screen'){
                $media = $css_obj['media'];
            }
            $url_array = parse_url($css_obj['href']);
            if(!wpcwv_isexternal($css_obj['href']) || file_exists($document_root.$url_array['path'])){
                $css_obj['href'] = $home_url.$url_array['path'];
            }
            $full_css_url = $css_obj['href'];
            $url = explode('?',$full_css_url);
            $url_array = parse_url($full_css_url);

            if($url_array['host'] == 'fonts.googleapis.com'){
                parse_str($url_array['query'], $get_array);
                if(!empty($get_array['family'])){
                    $font_array = explode('|',$get_array['family']);
                    foreach($font_array as $font){
                        $font_split = explode(':',$font);
                        $fonts_api_links[$font_split[0]] = explode(',',$font_split[1]);
                    }
                }
                str_replace_set($css,'', $html);
                continue;
            }
            $src = $url[0];
            $include_as_inline = 0;
            if(!empty($exclude_css)){
                foreach($exclude_css as $ex_css){
                    if(strpos($src, $ex_css)){
                        $include_as_inline = 1;
                    }
                }
            }
            $src = $full_css_url;
            if(!empty($src) && !wpcwv_isexternal($src) && !empty($include_as_inline) && endswith($src, '.css') ){
                $path = parse_url($src, PHP_URL_PATH);
                $filename = $document_root.$path;
                $inline_css_var = gz_relative_to_absolute_path($src,file_get_contents($filename));
                $inline_css[] = !empty($media) ? '@'.$media.'{'.$inline_css_var.'}' : $inline_css_var ;
                str_replace_set($css,'',$html);
            } elseif(!empty($src) && !wpcwv_isexternal($src) && endswith($src, '.css')) {
                $path = parse_url($src, PHP_URL_PATH);
                $filename = $document_root.$path;
                if(filesize($filename) > 0){
                    $inline_css_var = gz_relative_to_absolute_path($src,file_get_contents($filename));
                    $all_css .= !empty($media) ? '@'.$media.'{'.$inline_css_var.'}' : $inline_css_var ;
                }
                str_replace_set($css,'',$html);
            } elseif(endswith($full_css_url, '.css') || strpos($full_css_url, '.css?')) {
                $main_css_url[] = $full_css_url;
                str_replace_set($css,'',$html);
            }
        }
    }
    //exit;
    $css_exists = 0;
       
	  $css_exists = rand(1,1000);
	  $file = fopen($full_cache_path.'/all-css'.$css_exists.$css_ext,'c');
	  fwrite($file,$all_css);
	  fclose($file);
    
    wpcwv_prime_time('defer_css_done');


    if(!empty($fonts_api_links)){
        
        $all_links = '';
        foreach($fonts_api_links as $key => $links){
            $all_links .= $key.':'.implode(',',$links).'|';
        }
        $main_css_url[] = $secure."fonts.googleapis.com/css?family=".urlencode(trim($all_links,'|'));
    }
    $encoded_url = !empty(trim($encoded_url,'/')) ? '/'.trim($encoded_url,'/').'/' : '/';
    $html = gz_relative_to_absolute_path($home_url.'/test.html',$html);
    $html = str_replace_bulk($html);
    if(is_array($inline_css)){
        $inline_css = array_reverse($inline_css);
        for($i=0; $i < count($inline_css); $i++){
            $html = str_replace('<head itemscope itemtype="https://schema.org/WebSite">','<head itemscope itemtype="https://schema.org/WebSite"><style>'.$inline_css[$i].'</style>',$html);
        }
    }
    $encoded_url = !empty(trim($encoded_url,'/')) ? '/'.trim($encoded_url,'/').'/' : '/';
    if(!empty($css_exists)){
        $main_css_url[] = $home_url.'/wpcwv-cache'.$encoded_url.'all-css'.$css_exists.$css_ext;
    }
    if(!empty($js_exists)){
        $main_js_url = $home_url.'/wpcwv-cache'.$encoded_url.'all-js'.$js_exists.$js_ext;
        $lazy_load_js[] = $main_js_url;
    }
    $html = str_replace('</body>','<script>'.lazy_load_images().'</script></body>',$html);

    wpcwv_prime_time('html_done');
    return $html;

}
function lazy_load_images(){
    global $home_url, $full_url_without_param, $image_home_url,$exclude_lazyload,$main_css_url, $lazy_load_js,$document_root,$optimize_image_array,$lazyload_inner_js,$lazyload_inner_ads_js_arr;
    $script = 'var lazy_load_js='.json_encode($lazy_load_js).';
        var lazy_load_css='.json_encode($main_css_url).';
        var lazyload_inner_js = '.json_encode($lazyload_inner_js).';
        var lazyload_inner_ads_js = '.json_encode($lazyload_inner_ads_js_arr).';
        var wpcwv_first_js = false;
        var wpcwv_first_inner_js = false;
        var wpcwv_first_css = false;
        var wpcwv_first = false;
        var wpcwv_optimize_image = false;
        setTimeout(function(){load_extCss();},1000);
        setTimeout(function(){load_innerJS();},2000);
        /*load_extJS();*/
        window.addEventListener("load", function(event){
			setTimeout(function(){load_extJS();},3000);
            lazyloadimages(0);
        });
        window.addEventListener("scroll", function(event){
           setTimeout(function(){load_extJS();},1000);

        });
        function load_innerJS(){
            if(wpcwv_first_inner_js == false){
                for(var key in lazyload_inner_js){
                    if(lazyload_inner_js[key] != ""){
                        var s = document.createElement("script");
                        s.innerHTML =lazyload_inner_js[key];
                        document.getElementsByTagName("body")[0].appendChild(s);
                    }
                }
                wpcwv_first_inner_js = true;
            }
        }
        function load_extJS() {
            if(wpcwv_first_js == false && lazy_load_js.length > 0){
                lazy_load_js.forEach(function(src) {
                    var s = document.createElement("script");
                    s.type = "text/javascript";
                    s.src = src;
                    document.getElementsByTagName("head")[0].appendChild(s);

                });
                wpcwv_first_js = true;
            }
        }
    var exclude_lazyload = '.json_encode($exclude_lazyload).';
    var win_width = screen.availWidth;
    function load_extCss(){
        if(wpcwv_first_css == false && lazy_load_css.length > 0){
            lazy_load_css.forEach(function(src) {
                var load_css = document.createElement("link");
                load_css.rel = "stylesheet";
                load_css.href = src;
                load_css.type = "text/css";
                var godefer2 = document.getElementsByTagName("style")[0];
                godefer2.parentNode.insertBefore(load_css, godefer2);
            });
            wpcwv_first_css = true;
        }
    }

    if(exclude_lazyload.length > 0){
        for (i = 0; i < exclude_lazyload.length; i++) {
            var ex_class = exclude_lazyload[i];
            var ex_class_array = document.getElementsByClassName(ex_class);
            for (j = 0; j < ex_class_array.length; j++) {
                 var src = ex_class_array[j].getAttribute("data-src") ? ex_class_array[j].getAttribute("data-src") : ex_class_array[j].src ;
            }
        }
    }
    window.addEventListener("scroll", function(event){
         var top = this.scrollY;
         lazyloadimages(top);
         lazyloadiframes(top);

    });
    setInterval(function(){lazyloadiframes(top);},5000);
    setInterval(function(){lazyloadimages(0);},10);
    function lazyload_img(imgs,bodyRect,window_height,win_width){
        for (i = 0; i < imgs.length; i++) {

            if(imgs[i].getAttribute("data-class") == "LazyLoad"){
                var elemRect = imgs[i].getBoundingClientRect(),
                offset   = elemRect.top - bodyRect.top;
                if(elemRect.top != 0 && elemRect.top - window_height < 0 ){
                    /*console.log(imgs[i].getAttribute("data-src")+" -- "+elemRect.top+" -- "+window_height);*/
                    var src = imgs[i].getAttribute("data-src") ? imgs[i].getAttribute("data-src") : imgs[i].src ;
                    var srcset = imgs[i].getAttribute("data-srcset") ? imgs[i].getAttribute("data-srcset") : "";
                    imgs[i].src = src;
                    if(imgs[i].srcset != null & imgs[i].srcset != ""){
                        imgs[i].srcset = srcset;
                    }
                    delete imgs[i].dataset.class;
                    imgs[i].setAttribute("data-done","Loaded");
                }
            }
        }
    }
    function lazyload_video(imgs,bodyRect,window_height,win_width){
        for (i = 0; i < imgs.length; i++) {
            var source = imgs[i].getElementsByTagName("source")[0];
            if(source.getAttribute("data-class") == "LazyLoad"){
                var elemRect = imgs[i].getBoundingClientRect(),
                offset   = elemRect.top - bodyRect.top;

                if(elemRect.top - window_height < 0 ){
                    var src = source.getAttribute("data-src") ? source.getAttribute("data-src") : source.src ;
                    var srcset = source.getAttribute("data-srcset") ? source.getAttribute("data-srcset") : "";
                    imgs[i].src = src;
                    if(source.srcset != null & source.srcset != ""){
                        source.srcset = srcset;
                    }
                    delete source.dataset.class;
                    source.setAttribute("data-done","Loaded");
                }
            }
        }
    }
    function lazyloadimages(top){
        var imgs = document.getElementsByTagName("img");
        var ads = document.getElementsByClassName("lazyload-ads");
        var sources = document.getElementsByTagName("video");
        var bodyRect = document.body.getBoundingClientRect();
        var window_height = window.innerHeight;
        var win_width = screen.availWidth;
        lazyload_img(imgs,bodyRect,window_height,win_width);
        lazyload_ads(ads,bodyRect,window_height,win_width);
        lazyload_video(sources,bodyRect,window_height,win_width);
    }
    function lazyload_ads(ads,bodyRect,window_height,win_width){
        for (i = 0; i < ads.length; i++) {
            var classname = ads[i].className;
            if(classname.trim() == "lazyload-ads"){
                var elemRect = ads[i].getBoundingClientRect(),
                offset   = elemRect.top - bodyRect.top;
                if(elemRect.top != 0 && elemRect.top - window_height < 200 ){
                    var id = ads[i].id ;
                    for (j = 0; j < lazyload_inner_ads_js.length; j++) {
                        str = lazyload_inner_ads_js[j];
                        if(str.indexOf(id) > -1){
                            var s = document.createElement("script");
                            var code = str;
                            s.onload = function(){
                              console.log("loaded");
                            }
                            try {
                              s.appendChild(document.createTextNode(code));
                              document.getElementsByTagName("head")[0].appendChild(s);
                            } catch (e) {
                              s.text = code;
                              document.getElementsByTagName("head")[0].appendChild(s);
                            }
                            break;
                        }
                    }
                    delete ads[i].classList.remove("lazyload-ads");
                }
            }
        }
    }
    function lazyloadiframes(top){
        var bodyRect = document.body.getBoundingClientRect();
        var window_height = window.innerHeight;
        var win_width = screen.availWidth;
        var iframes = document.getElementsByTagName("iframe");
        lazyload_img(iframes,bodyRect,window_height,win_width);
    }';
    return $script;
}
if(!empty($_REQUEST['set-opt'])){
    exit;
}

function sanitize_output($buffer){

    $search = '/<!--(.|\s)*?-->/';

    $replace = '';

    $buffer = preg_replace($search, $replace, $buffer);

    return $buffer;
}

$uri_parts = explode('?', $_SERVER['REQUEST_URI'], 2);
$current_url = $uri_parts[0];
$url_array = explode('/',trim($current_url,'/'));
$url_array = array_reverse($url_array);
if(!empty($_REQUEST['testing'])){
    echo '<pre>';
    print_r($_SERVER);
    exit;
    echo $current_url;
    print_r($url_array);
    exit;
}
function create_log($data){
    global $document_root;
    $f = fopen($document_root.'/cache_log.txt','a');
    fwrite($f,$data."\n");
    fclose($f);
}

function gz_relative_to_absolute_path($url, $string){
    global $image_home_url, $home_url,$document_root,$secure,$fonts_api_links;
    $url_arr = parse_url($url);
    $url = $home_url.$url_arr['path'];
    $matches = wpcwv_get_tags_data($string,'url(',')');
	  $replaced = array();
    $replaced_new = array();
    $replace_array = explode('/',str_replace('\'','/',$url));
    $replace_array = array_reverse($replace_array);
    unset($replace_array[0]);
    foreach($matches as $match){
		   if(strpos($match,'data:') !== false){
            continue;
        }
       $org_match = $match;
		   $match1 = str_replace(array('url(',')',"url('","')",')',"'",'"','&#039;'), '', html_entity_decode($match));
       if(strpos($match1,'//') > 7){
          $match1 = substr($match1, 0, 7).str_replace('//','/', substr($match1, 7));
       }
        
      $url_arr = parse_url($match1);
      $match1 = $url_arr['path'];
		
        if(strpos($match,'fonts.googleapis.com') !== false){
            
            $string = str_replace('@import '.$match.';','', $string);
            parse_str($url_arr['query'], $get_array);
            if(!empty($get_array['family'])){
                $font_array = explode('|',$get_array['family']);
                foreach($font_array as $font){
                    $font_split = explode(':',$font);
                    $fonts_api_links[$font_split[0]] = explode(',',$font_split[1]);
                }
            }
            
            continue;
        }
        if(wpcwv_isexternal($match1)){
            continue;
        }
        if(empty(trim($match1))){
            $string = str_replace($org_match, '', $string);
            continue;
        }
        
        $url_array = explode('/',$match1);
        $image_name = end($url_array);
        $image_start_array = trim($url_array[0]);
        if(empty($image_start_array)){
            $replacement = $image_home_url.trim($match1);
            if(strpos($replacement,'.jpg') || strpos($replacement,'.png') || strpos($replacement,'.jpeg') || strpos($replacement,'.webp') ){
                $replacement = str_replace(array("'",$home_url),array('',$image_home_url), $replacement);
                $cache_img = get_image_cache_path($replacement,'','');
                if(file_exists($document_root.$cache_img)){
                    $replacement = $image_home_url.$cache_img;
                }
            }
        } else {
            $i=1;
            if(strpos($match1,'.jpg') === false && strpos($match1,'.png') === false && strpos($match1,'.jpeg') === false && strpos($match1,'.woff') === false && strpos($match1,'woff2') === false && strpos($match1,'svg') === false && strpos($match1,'ttf') === false  && strpos($match1,'eot') === false && strpos($match1,'gif') === false && strpos($match1,'.webp') === false ){
                continue;
            }
            $replace_array1 = $replace_array;
            foreach($url_array as $key => $slug){
                $slug = str_replace("'", '', $slug);
                if($slug == '..' ){
                    if($url != $home_url){
                        unset($replace_array1[$i]);
                    }
                    unset($url_array[$key]);
                }

                $i++;
            }

            $replace_array1 = array_reverse($replace_array1);
            $replacement = trim(implode('/',$replace_array1),'/').'/'.trim(implode('/',$url_array),'/');
            if(strpos($replacement,'jpg') || strpos($replacement,'png') || strpos($replacement,'jpeg') || strpos($replacement,'.webp') ){
                $replacement = str_replace(array("'",$home_url),array('',$image_home_url), $replacement);
                $cache_img = get_image_cache_path($replacement,'','');
                if(file_exists($document_root.$cache_img)){
                    $replacement = $image_home_url.$cache_img;
                }
            }

        }
		if(!in_array($image_name , $replaced)){
			$replaced['org'] = $match;
			$replaced_new['match'][] = $match;
            $replaced_new['replacement'][] = 'url('.$replacement.')';
        }
        $string = str_replace($replaced_new['match'], $replaced_new['replacement'], $string);
    }

    return $string;
}

function get_random_string(){
    $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
     $string = '';
     $random_string_length = 20;
     $max = strlen($characters) - 1;
     for ($i = 0; $i < $random_string_length; $i++) {
          $string .= $characters[mt_rand(0, $max)];
     }
    return $string;
}

  function rrmdir($dir) {
  if (is_dir($dir)) {
    $objects = scandir($dir);
    foreach ($objects as $object) {
      if ($object != "." && $object != "..") {
        if (filetype($dir."/".$object) == "dir")
            rrmdir($dir."/".$object);
        else{
            unlink($dir."/".$object);
            }
      }
    }
    reset($objects);
    rmdir($dir);
  }
 }
if(!empty($_REQUEST['delete-wpcwv-cache'])){
    rrmdir($document_root.'/wpcwv-cache');
    exit;
}

function compress_js($html){
    $html = JSMin::minify($html);
    return $html;
}
function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}
function wpcwv_setAllLinks($data){
    $comment_tag = wpcwv_get_tags_data($data,'<!--','-->');
    foreach($comment_tag as $comment){
        $data = str_replace($comment,'',$data);
    }
    $script_tag = wpcwv_get_tags_data($data,'<script','</script>');
    $img_tag = wpcwv_get_tags_data($data,'<img','>');
    $link_tag = wpcwv_get_tags_data($data,'<link','>');
    $style_tag = wpcwv_get_tags_data($data,'<style','</style>');
    $iframe_tag = wpcwv_get_tags_data($data,'<iframe','>');
    $video_tag = wpcwv_get_tags_data($data,'<video','</video>');
    return array('script'=>$script_tag,'img'=>$img_tag,'link'=>$link_tag,'style'=>$style_tag,'iframe'=>$iframe_tag,'video'=>$video_tag);
}

function wpcwv_get_tags_data($data,$start_tag,$end_tag){
    $data_exists = 0; $i=0;
    $tag_char_len = strlen($start_tag);
    $end_tag_char_len = strlen($end_tag);
    $script_array = array();
    while($data_exists != -1 && $i<500) {
        $data_exists = strpos($data,$start_tag,$data_exists);
        if(!empty($data_exists)){
            $end_tag_pointer = strpos($data,$end_tag,$data_exists);
            $script_array[] = substr($data, $data_exists, $end_tag_pointer-$data_exists+$end_tag_char_len);
            $data_exists = $end_tag_pointer;
        }else{
            $data_exists = -1;
        }
        $i++;
    }
    return $script_array;
}
function remove_oldfiles($dir) {
  if (is_dir($dir)) {
    $objects = scandir($dir);
    foreach ($objects as $object) {
      if ($object != "." && $object != "..") {
        if (filetype($dir."/".$object) == "dir"){
            rrmdir($dir."/".$object); 
        }
        else{ 
                if(time() - filemtime($dir."/".$object) > 18000 && strpos($object,'all-js') !== false ){
                    unlink($dir."/".$object);
                }
            }
      }
    }
    reset($objects);
  }
 }
function wpcwv_start_optimization_callback() {
    ob_start("get_site_optimized");

}

function wpcwv_ob_end_flush() {
	if (ob_get_level() != 0) {
		ob_end_flush();
     }
}
register_shutdown_function('wpcwv_ob_end_flush');
add_action('wp_loaded', 'wpcwv_start_optimization_callback',1);
