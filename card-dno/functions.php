<?php
if ( ! class_exists('DNOCard') )
{
	class DNOCard
	{
		/**
		 * Load the template filters.
		 * 
		 * @author Jan R. Durand
		 * @version 1.0
		 */
		public function __construct()
		{
			// Update the permitted shortcode attribute the user may use and overrride the template defaults as needed.
			add_filter( 'cn_list_atts_permitted-card-dno' , array(&$this, 'initShortcodeAtts') );
			add_filter( 'cn_list_atts-card-dno' , array(&$this, 'initTemplateOptions') );
			add_filter( 'cn_list_before-card-dno', array(&$this, 'displayBeforeList'), 10, 2 );
			add_filter( 'cn_list_after-card-dno', array(&$this, 'displayAfterList'), 10, 2 );
			add_filter( 'query_vars', array(&$this, 'registerQueryVariables') );
			add_filter( 'cn_phone_number', array(&$this, 'modifyPhoneNumber' ) );
			
			// Enqueue javscript
			wp_enqueue_script( 'card-dno', home_url() . "/wp-content/connections_templates/card-dno/template.js" );
		}

		/**
		 * Register the valid query variables.
		 * 
		 * @param array $var
		 * @return array
		 */
		public function registerQueryVariables($var)
		{
		/*	These are already registered:
			$var[] = 'cn-cat';			// category id
			$var[] = 'cn-cat-slug';		// category slug
			$var[] = 'cn-country';		// country
			$var[] = 'cn-region';		// state
			$var[] = 'cn-locality';		// city
			$var[] = 'cn-postal-code';	// zipcode
			$var[] = 'cn-s';			// search term
			$var[] = 'cn-pg';			// page
			$var[] = 'cn-entry-slug';	// entry slug
			$var[] = 'cn-token';		// security token; WP nonce
			$var[] = 'cn-id';			// entry ID
			$var[] = 'cn-vc';			// download vCard, BOOL 1 or 0 [used only in links from the admin for unlisted entry's vCard]
			$var[] = 'cn-process';		// various processes [ vcard || add || edit || moderate || delete ]
			$var[] = 'cn-view';			// current view [ landing || list || detail ]
		*/	
			return $var;
		}
				
		/**
		 * Initiate the permitted template shortcode options and load the default values.
		 * 
		 * @author Jan R. Durand
		 * @version 1.0
		 */
		public function initShortcodeAtts( $permittedAtts = array() )
		{
			$permittedAtts['search_terms'] = NULL;
			$permittedAtts['enable_search'] = TRUE;
			$permittedAtts['enable_pagination'] = TRUE;
			$permittedAtts['page_limit'] = 20;
			$permittedAtts['pagination_position'] = 'after'; 
			$permittedAtts['enable_category_select'] = TRUE;
			$permittedAtts['show_empty_categories'] = TRUE;
			$permittedAtts['show_category_count'] = FALSE;
			
			return $permittedAtts;
		}
		
		/**
		 * Initiate the template options using the user supplied shortcode option values.
		 * 
		 * @author Jan R. Durand
		 * @version 1.0
		 */
		public function initTemplateOptions($atts)
		{
			//$convert = new cnFormatting();
			if ( !empty( $atts['search_terms'] ) )
				$atts['search_terms'] = trim( $atts['search_terms'] );
			
			// Limit number of entries per page
			$atts['limit'] = $atts['page_limit']; 
			
			$this->atts = $atts;
			
			return $atts;
		}
		
		/**
		 * Generate content to be displayed before the list of entries.
		 * 
		 * @author Jan R. Durand
		 * @version 1.0
		 */
		public function displayBeforeList($output, $entries)
		{
			$output .= "<div style='clear: both;'>";
			
			// Display category selection dropdown box
			$output .= $this->getCategorySelectControl();
			
			// Display search input box
			$output .= $this->getSearchControl();
			
			$output .= "</div>";
						
			// Display current search terms
			if ( $this->atts['enable_search'] && $this->atts['search_terms'] ) {
				$output .= "<div class='gradient-msg'>Search: {$this->atts['search_terms']}</div>";
			}			
			
			// Display page control
			$output .= $this->getPageControl( 'before' );
						
			return $output;
		}

		/**
		 * Generate content to be displayed after the list of entries.
		 * 
		 * @author Jan R. Durand
		 * @version 1.0
		 */
		public function displayAfterList($output, $entries)
		{
			// Display page control
			$output .= $this->getPageControl( 'after' );
			
			return $output;
		}

		/**
		 * Modify how phone numbers are displayed.
		 * 
		 * @author Jan R. Durand
		 * @version 1.0
		 */		
		public function modifyPhoneNumber( $phoneNumber ) {
			
			$search = array( 'Work Phone', 'Home Phone', ' Phone', 'Work Fax');
			$replace = array( 'Tel', 'Tel', '', 'Fax' );
			$phoneNumber->name = trim( str_ireplace( $search, $replace, $phoneNumber->name ) );
			
			return $phoneNumber;
		}		

		
		private function getCategories( $parent = 0, $level = 0 )
		{
			global $wpdb;
			
			// Retrieve all sub-categories of parent
			$count_query = "(SELECT COUNT(*) FROM " . CN_TERM_RELATIONSHIP_TABLE . " AS tr " .
						   "INNER JOIN " . CN_ENTRY_TABLE . " AS e ON tr.entry_id = e.id " .
						   "WHERE e.visibility = 'public' AND tr.term_taxonomy_id = tt.term_taxonomy_id)";
			$results =  $wpdb->get_results( $wpdb->prepare( 
				"SELECT t.term_id, t.name, $count_query AS entry_count " .
				"FROM " . CN_TERMS_TABLE . " AS t INNER JOIN " . CN_TERM_TAXONOMY_TABLE . " AS tt ON t.term_id = tt.term_id " .
				"WHERE t.term_id <> 1 AND tt.taxonomy = 'category' AND tt.parent = $parent" ) );
			
			if ( empty( $results ) ) return array();
			
			// Sort results in alphabetical order
			usort($results, array(&$this, 'sortTermsByName') );	
			
			foreach ( $results as $result ) {
				$categories[$result->term_id] = array( 'level' => $level, 'name' => $result->name, 'count' => $result->entry_count );
				$categories += $this->getCategories( $result->term_id, $level + 1 );
			}
			
			return $categories;
		}
		
		private function sortTermsByName($a, $b)
		{
			return strcmp($a->name, $b->name);
		}
		
		private function getSearchControl() {
			if ( !$this->atts['enable_search'] ) return '';
			
			$output = "<div id='bd-search'>";
			$output .= "<input type='text' id='bd-search-input' name='cn-s' value='{$this->atts['search_terms']}' placeholder='Search'/>";
			$output .= "<input type='submit' value='' id='bd-search-button' />";
			$output .= "</div>";
			
			return $output;
		}
		
		private function getCategorySelectControl() {
			if ( !$this->atts['enable_category_select'] ) return '';
			
			$cat_id = get_query_var( 'cn-cat' ); 
			$output = "<select id='bd-category' name='cn-cat'>";
			$output .= "<option value=''>Select Category</option>"; 
			$categories = $this->getCategories();
			foreach ( $categories as $id => $category ) {
				if ( !$this->atts['show_empty_categories'] && ( $category['count'] == 0 ) ) continue; 
				$count = $this->atts['show_category_count'] ? "({$category['count']})" : '';
				$selected = ( $cat_id == $id ) ? "selected='selected'" : '';
				$output .= "<option $selected value='$id'>" . str_repeat( '&nbsp;' , $category['level'] * 4 ) . "{$category['name']} $count</option>"; 
			}
			$output .= "</select>";
		
			return $output;			
		}
		
		private function getPageControl( $position = 'both' ) {
			global $connections;
			
			if ( !$this->atts['enable_pagination'] ) return '';
			if ( ( $position != 'both' ) && ( $this->atts['pagination_position'] != $position ) ) return '';
			if ( $connections->resultCountNoLimit <= $this->atts['page_limit'] ) return '';
					
			$numPages = ceil( $connections->resultCountNoLimit / $this->atts['page_limit'] );
			$pageNumber = get_query_var( 'cn-pg' ) ? get_query_var( 'cn-pg' ) : 1; 
			$maxPageNumbers = 10;
	
			if ( $pageNumber == 1 ) {
				$startPageNumber = 1;
				$endPageNumber = min( $maxPageNumbers, $numPages );
			}
			else if ( $pageNumber == $numPages ) {
				$startPageNumber = max( $numPages - $maxPageNumbers + 1, 1 );
				$endPageNumber = $numPages;
			}
			else {
				$endPageNumber = min( $pageNumber + ceil( $maxPageNumbers / 2 ) - 1, $numPages );
				$startPageNumber = max( $endPageNumber - $maxPageNumbers + 1, 1 );
			}
				
			$output = "<div class='bd-page-control'>";
			
			$request_uri = explode( '?', $_SERVER['REQUEST_URI'] );
			$request_uri = $request_uri[0] . '?';
			foreach ( $_GET as $param => $value ) {
				if ( $param != 'cn-pg' )
					$request_uri .= "$param=$value&";
			}
			$request_uri = substr( $request_uri, 0, -1);
			
			if ( $pageNumber > 1 ) {
				$output .= "<a class='page-number' href='$request_uri&cn-pg=1' title='Go to first page'>&lt;&lt;&lt;</a>";
				$output .= "<a class='page-number' href='$request_uri&cn-pg=" . ( $pageNumber - 1 ) . "' title='Go to previous page'>&lt;&lt;</a>";
			}		
			
			for ( $i = $startPageNumber; $i <= $endPageNumber; ++$i ) {
				
				if ( $i == $pageNumber ) { 
					$output .= "<span class='current-page-number'>$i</span>";
				}
				else {
					$output .= "<a class='page-number' href='$request_uri&cn-pg=$i' title='Go to page $i'>$i</a>";
				}
			}
			
			if ( $pageNumber < $numPages ) {
				$output .= "<a class='page-number' href='$request_uri&cn-pg=" . ( $pageNumber + 1 ) . "' title='Go to next page'>&gt;&gt;</a>";
				$output .= "<a class='page-number' href='$request_uri&cn-pg=$numPages' title='Go to last page'>&gt;&gt;&gt;</a>";
			}		
			
			$output .= "</div>";
			
			return $output;
		}		
	}
	
	//print_r($this);
	$this->DNOCard = new DNOCard();
}
?>