<?php 


Class Gecka_Submenu_Submenu {
	
	private $Options;
	private $DefaultOptions = array( 	'menu' => '', 
	                                    'container' => 'div', 
	                                    'container_class' => 'submenu', 
	                                    'container_id' => '', 
	                                    'menu_class' => 'menu', 
	                                    'menu_id' => '',
	                                    'echo' => false, 
	                                    'fallback_cb' => 'wp_page_menu', 
	                                    'before' => '', 
	                                    'after' => '', 
	                                    'link_before' => '', 
	                                    'link_after' => '',
	                                    'depth' => 0, 
	                                    'walker' => '', 
	                                    'theme_location' => '',
	                                    
	                                    'is_gk_submenu' => true,
	                                    'submenu' => null,
									 	'child_of'=> null,
										'type' => 'post_type',
									 	'title'	=> '',
									 	'auto_title' => false,
									 	'show_description' => false,
										'thumbnail' => null,
										'auto_title' => false
									
									);
	
	// Always holds the latest Top level menu Item
	private $top_level_item;
									
	public function __construct ($Options = array()) 
	{
		
		$this->Options = wp_parse_args($Options, $this->DefaultOptions);
		
	}
	
	public function Show($Options = null) 
	{
		
		echo $this->Build($Options);
		
	}
	
	
	public function Get($Options = null) 
	{		
		return $this->Build($Options);
	}
	
	public function get_top_level_item () 
	{
		return $this->top_level_item;
	}
	
	public function Widget($args, $instance) 
	{

    	extract( wp_parse_args($args, $this->DefaultOptions), EXTR_SKIP);
        
        $instance['container_class'] = 'submenu-widget';
        $instance['is_gk_submenu'] = 'widget';
    	
    	$out = $this->Get($instance);
        
    	if($out) {
    		 
    		$auto_title = isset($instance['auto_title']) && $instance['auto_title'] ? true : false;
    		
    		$title = '';
    		if( $auto_title &&  $this->top_level_item) {
    			$title = $this->top_level_item->title;
    		}
    		else $title = $instance['title'];
       
    		$title = apply_filters('widget_title', $title, $instance);

    		echo $before_widget;

    		if($title) {
    			echo $before_title . apply_filters('widget_title', $title, $instance) . $after_title;
    		}
    		 
    		echo $out;
    		 
    		echo $after_widget;
    	}
	}
	
	private function Build($Options = null) 
	{
	
		if( $Options !== null ) $Options = wp_parse_args($Options, $this->Options) ;
		else $Options = $this->Options;
		
		$Options = wp_parse_args($Options, $this->DefaultOptions);
		
		extract($Options);
		
		$depth = (int)$depth;
        $show_description = $show_description ? true : false;
        
        if(isset($child_of)) $submenu = $child_of;
		
		// if no menu specified, gets the lowest ID menu
		if(!$menu || !is_nav_menu($menu)) {
			
			$menus = wp_get_nav_menus();
			
			foreach ( $menus as $menu_maybe ) {
				if ( $menu_items = wp_get_nav_menu_items($menu_maybe->term_id) ) {
					$menu = $menu_maybe->term_id;
					break;
				}
			}
		}
		
		// still can't find a menu, we exit
		if(!$menu || !is_nav_menu($menu)) return;
		
		$menu_items = wp_get_nav_menu_items($menu);
		
		if(is_tax()) $_type = 'taxonomy';
		else $_type = 'post_type';
				
		// current page is top level element
		if( $submenu === 'current' ) {
		    global $wp_query;
			$submenu = $this->get_associated_nav_menu_item($wp_query->get_queried_object_id(), &$menu_items, $_type);
		}
		        
		// a submenu has been specified
		if( $submenu !== 0 ) {
       
            if(!$submenu) return;
       
            $submenu_item = $submenu;
            
            if( !is_object($submenu) ) {
                
                $submenu_item = $this->get_menu_item ($submenu, &$menu_items);
		        
		        
		        if( !$submenu_item ) $submenu_item = $this->get_associated_nav_menu_item($submenu, &$menu_items, $type);
                if(!$submenu_item) return;
		    }
            
		    if( !$this->menu_item_has_child($submenu_item->ID, &$menu_items)) return;
		    
		    $submenu_id = $submenu_item->ID;
		    
		    $this->top_level_item = $submenu_item;
        
            global $GKSM_ID, $GKSM_MENUID;
		    $GKSM_ID = $submenu_id; $GKSM_MENUID = $menu;
		
		}
        
        if(!strpos($container_class, ' ')) {
            $slug = '';
            if( !empty($GLOBALS['wp_query']->get_queried_object()->post_name) ) $slug = $GLOBALS['wp_query']->get_queried_object()->post_name;
            else if( !empty($GLOBALS['wp_query']->get_queried_object()->$slug) ) $slug = $GLOBALS['wp_query']->get_queried_object()->slug;
            
            $container_class .= " $container_class-" . $slug ;
        
        }// gets the nav menu
        
		$args =  array( 'container_class' => $container_class,
		                 'menu'=> $menu, 
		                 'show_description'=> $show_description, 
		                 'depth'=>$depth,
		                 'is_gk_submenu'=>$is_gk_submenu );
	
		$out = wp_nav_menu( wp_parse_args($args, $Options) );
		
        // reset global variables
		$GKSM_ID = $GKSM_MENUID = null;
			
		return $out;
	}
	
    /**
	 * Gets a menu item from a list of menu items, avoiding SQL queries
	 * @param int $item_id id of item to retreive
	 * @param array $menu_items array of menu items
	 * @return object $Item a menu item object or false
	 */
	private function get_menu_item ($item_id, &$menu_items)  {
	    if(!is_array($menu_items)) return false;
        foreach($menu_items as $Item) {
            if($Item->ID == $item_id) return $Item;
        }
        return false;
    }
    
    private function menu_item_has_child ($item_id, &$menu_items)  {
	    if(!is_array($menu_items)) return false;
        foreach($menu_items as $Item) {
            if($Item->menu_item_parent == $item_id) return true;
        }
        return false;
    }
	
	public function get_associated_nav_menu_item($object_id, &$menu_items, $type='post_type', $offset=0) {
	
	    $offset = abs( (int)$offset );
	
	    $AssociatedMenuItems = $this->get_associated_nav_menu_items( $object_id, &$menu_items, $type );
         
        if( !$num = sizeof($AssociatedMenuItems) ) return false;
          
        if($offset>$num) $offset = $num-1;
         
        return $AssociatedMenuItems[$offset];
        
	}
    
    function get_associated_nav_menu_items( $object_id, &$menu_items, $object_type = 'post_type') {
	    $object_id = (int) $object_id;
	    $_menu_items = array();

	    foreach( $menu_items as $menu_item ) {

            if($menu_item->object_id == $object_id && $menu_item->type === $object_type) {
			    $_menu_items[] = $menu_item;
		    }
	    }

	    return $_menu_items;  
    }    
}
