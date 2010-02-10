<?php

/*
 * Plugin Name: DHResume
 * Description: Provide a service which is like a resume directory.
 * Author: Fabien Roulette
 * Plugin URI: 
 * Version: 1.0
 * =======================================================================
 */    
                         
# DhResume paths.
define('DHRDIR', dirname(__FILE__) . '/');                
define('XMFP_INCLUDE_PATH', DHRDIR . 'xmfp/'); 
    
           
            
class DHResume {               
             
  # Internal
  var $version = '1.0';
  
  private $id_index; 
  private $id_vcard;
  private $id_vevent;
  private $display_option_list;
 
  # __construct()
  function DHResume()
  {              
    global $wpdb, $wp_version;
                                     
    # Table names init
    $this->db = array(
      'index'	=> $wpdb->prefix . 'dhr_index',
      'education'	=> $wpdb->prefix . 'dhr_education',
      'experience'	=> $wpdb->prefix . 'dhr_experience',     
      'contact'	=> $wpdb->prefix . 'dhr_contact',   
      'vcard'	=> $wpdb->prefix . 'dhr_vcard',
      'title'	=> $wpdb->prefix . 'dhr_title',
      'adr'	=> $wpdb->prefix . 'dhr_adr',
      'url'	=> $wpdb->prefix . 'dhr_url',
      'tel'	=> $wpdb->prefix . 'dhr_tel',     
      'org'	=> $wpdb->prefix . 'dhr_org',   
      'email'	=> $wpdb->prefix . 'dhr_email',
      'name'	=> $wpdb->prefix . 'dhr_name',
      'vevent'	=> $wpdb->prefix . 'dhr_vevent'
    );                                    
    
    # Are we running the new admin panel (2.5+) ?
    $this->newadmin = version_compare($wp_version, '2.5.0', '>=');
    
    # Is installed ?
    $this->installed = get_option('dhr_version');
    
    
    # Actions
    add_action('activate_DHResume/dhresume.php', array(&$this, 'activate'));                # Plugin activated
    add_action('deactivate_DHResume/dhresume.php', array(&$this, 'deactivate'));            # Plugin deactivated
    add_action('dhr_resume', array(&$this, 'exec_resume'), 10, 1);							# Add a new action to permit to execut it from an XML-RPC request
    add_shortcode( 'dhr_display' , array(&$this, 'display_name_adr') );						# Add a shortcode
    add_action( 'admin_menu', array(&$this, 'menu_admin') );
    add_action('wp_head', array(&$this, 'hResume_WP_wp_head'));								# CSS for display a Resume
    
    
   }
  
   /**
   * Called when plugin is activated 
   * Create each tables
   *
   */ 
  function activate($force_install = false)
  {
    global $wpdb;
    
                                                 
    // only re-install if new version or uninstalled
    if($force_install || ! $this->installed || $this->installed != $this->version) 
    {			
			# dhr_index
			$wpdb->query( "CREATE TABLE {$this->db['index']} (
							     `idhresume_index` INT NOT NULL AUTO_INCREMENT ,
								  `url` VARCHAR(255) NULL ,
								  `last_update` TIMESTAMP NULL ,
								  `hash` VARCHAR(32) NULL ,
								  PRIMARY KEY (`idhresume_index`)
						   );" ); 
		 
		 # dhr_vcard 			               
     $wpdb->query(  "CREATE TABLE {$this->db['vcard']} (
  						    `idhresume_vcard` INT NOT NULL AUTO_INCREMENT,
							`hresume_index_idhresume_index` INT NOT NULL ,
							 PRIMARY KEY (`idhresume_vcard`, `hresume_index_idhresume_index`) ,
							 INDEX `fk_hresume_vcard_hresume_index1` (`hresume_index_idhresume_index` ASC) ,
							 CONSTRAINT `fk_hresume_vcard_hresume_index1`
							    FOREIGN KEY (`hresume_index_idhresume_index` )
							    REFERENCES `{$this->db['index']}` (`idhresume_index` )
							    ON DELETE NO ACTION
							    ON UPDATE NO ACTION
  						 );" );              
  	 
  	 # dhr_vevent 				 
     $wpdb->query(  "CREATE TABLE {$this->db['vevent']} (
  						      `idhresume_vevent` INT NOT NULL AUTO_INCREMENT,
							  `hresume_index_idhresume_index` INT NOT NULL ,
							  `dtstart` DATE NULL ,
							  `summary` TEXT NULL ,
							  `dtend` DATE NULL ,
							  `url` VARCHAR(255) NULL ,
							  `location` VARCHAR(255) NULL ,
							  PRIMARY KEY (`idhresume_vevent`, `hresume_index_idhresume_index`) ,
							  INDEX `fk_hresume_vevent_hresume_index1` (`hresume_index_idhresume_index` ASC) ,
							  CONSTRAINT `fk_hresume_vevent_hresume_index1`
							    FOREIGN KEY (`hresume_index_idhresume_index` )
							    REFERENCES `{$this->db['index']}` (`idhresume_index` )
							    ON DELETE NO ACTION
							    ON UPDATE NO ACTION
  						 );" );  
  						 
    # dhr_education			 
    $wpdb->query(  "CREATE TABLE {$this->db['education']} (
						`idhresume_education` INT NOT NULL AUTO_INCREMENT,
					  `hresume_index_idhresume_index` INT NOT NULL ,
					  `hresume_vcard_idhresume_vcard` INT NOT NULL ,
					  `hresume_vevent_idhresume_vevent` INT NOT NULL ,
					  PRIMARY KEY (`idhresume_education`, `hresume_index_idhresume_index`, `hresume_vcard_idhresume_vcard`, `hresume_vevent_idhresume_vevent`) ,
					  INDEX `fk_hresume_education_hresume_index` (`hresume_index_idhresume_index` ASC) ,
					  INDEX `fk_hresume_education_hresume_vcard1` (`hresume_vcard_idhresume_vcard` ASC) ,
					  INDEX `fk_hresume_education_hresume_vevent1` (`hresume_vevent_idhresume_vevent` ASC) ,
					  CONSTRAINT `fk_hresume_education_hresume_index`
					    FOREIGN KEY (`hresume_index_idhresume_index` )
					    REFERENCES `{$this->db['index']}` (`idhresume_index` )
					    ON DELETE NO ACTION
					    ON UPDATE NO ACTION,
					  CONSTRAINT `fk_hresume_education_hresume_vcard1`
					    FOREIGN KEY (`hresume_vcard_idhresume_vcard` )
					    REFERENCES `{$this->db['vcard']}` (`idhresume_vcard` )
					    ON DELETE NO ACTION
					    ON UPDATE NO ACTION,
					  CONSTRAINT `fk_hresume_education_hresume_vevent1`
					    FOREIGN KEY (`hresume_vevent_idhresume_vevent` )
					    REFERENCES `{$this->db['vevent']}` (`idhresume_vevent` )
					    ON DELETE NO ACTION
					    ON UPDATE NO ACTION
    				 );" ); 
  						 
  	 # dhr_experience				 
     $wpdb->query(  "CREATE TABLE {$this->db['experience']} (
					  `idhresume_experience` INT NOT NULL AUTO_INCREMENT,
					  `hresume_index_idhresume_index` INT NOT NULL ,
					  `hresume_vevent_idhresume_vevent` INT NOT NULL ,
					  `hresume_vcard_idhresume_vcard` INT NOT NULL ,
					  PRIMARY KEY (`idhresume_experience`, `hresume_index_idhresume_index`, `hresume_vevent_idhresume_vevent`, `hresume_vcard_idhresume_vcard`) ,
					  INDEX `fk_hresume_experience_hresume_index1` (`hresume_index_idhresume_index` ASC) ,
					  INDEX `fk_hresume_experience_hresume_vevent1` (`hresume_vevent_idhresume_vevent` ASC) ,
					  INDEX `fk_hresume_experience_hresume_vcard1` (`hresume_vcard_idhresume_vcard` ASC) ,
					  CONSTRAINT `fk_hresume_experience_hresume_index1`
					    FOREIGN KEY (`hresume_index_idhresume_index` )
					    REFERENCES `{$this->db['index']}` (`idhresume_index` )
					    ON DELETE NO ACTION
					    ON UPDATE NO ACTION,
					  CONSTRAINT `fk_hresume_experience_hresume_vevent1`
					    FOREIGN KEY (`hresume_vevent_idhresume_vevent` )
					    REFERENCES `{$this->db['vevent']}` (`idhresume_vevent` )
					    ON DELETE NO ACTION
					    ON UPDATE NO ACTION,
					  CONSTRAINT `fk_hresume_experience_hresume_vcard1`
					    FOREIGN KEY (`hresume_vcard_idhresume_vcard` )
					    REFERENCES `{$this->db['vcard']}` (`idhresume_vcard` )
					    ON DELETE NO ACTION
					    ON UPDATE NO ACTION
  						 );" );  						 
		                      
		 # dhr_contact 			
     $wpdb->query(  "CREATE TABLE {$this->db['contact']} (
  						      `idhresume_contact` INT NOT NULL AUTO_INCREMENT,
							  `hresume_index_idhresume_index` INT NOT NULL ,
							  `hresume_vcard_idhresume_vcard` INT NOT NULL ,
							  PRIMARY KEY (`idhresume_contact`, `hresume_index_idhresume_index`, `hresume_vcard_idhresume_vcard`) ,
							  INDEX `fk_hresume_contact_hresume_index1` (`hresume_index_idhresume_index` ASC) ,
							  INDEX `fk_hresume_contact_hresume_vcard1` (`hresume_vcard_idhresume_vcard` ASC) ,
							  CONSTRAINT `fk_hresume_contact_hresume_index1`
							    FOREIGN KEY (`hresume_index_idhresume_index` )
							    REFERENCES `{$this->db['index']}` (`idhresume_index` )
							    ON DELETE NO ACTION
							    ON UPDATE NO ACTION,
							  CONSTRAINT `fk_hresume_contact_hresume_vcard1`
							    FOREIGN KEY (`hresume_vcard_idhresume_vcard` )
							    REFERENCES `{$this->db['vcard']}` (`idhresume_vcard` )
							    ON DELETE NO ACTION
							    ON UPDATE NO ACTION
  						 );" ); 			      
      
		 # dhr_title 			
     $wpdb->query(  "CREATE TABLE {$this->db['title']} (
  						      `idhresume_title` INT NOT NULL AUTO_INCREMENT,
							  `hresume_vcard_idhresume_vcard` INT NOT NULL ,
							  `title` VARCHAR(255) NULL ,
							  PRIMARY KEY (`idhresume_title`, `hresume_vcard_idhresume_vcard`) ,
							  INDEX `fk_hresume_title_hresume_vcard1` (`hresume_vcard_idhresume_vcard` ASC) ,
							  CONSTRAINT `fk_hresume_title_hresume_vcard1`
							    FOREIGN KEY (`hresume_vcard_idhresume_vcard` )
							    REFERENCES `{$this->db['vcard']}` (`idhresume_vcard` )
							    ON DELETE NO ACTION
							    ON UPDATE NO ACTION
  						 );" ); 			      
     
     		 # dhr_adr 			
     $wpdb->query(  "CREATE TABLE {$this->db['adr']} (
							  `idhresume_adr` INT NOT NULL AUTO_INCREMENT,
							  `hresume_vcard_idhresume_vcard` INT NOT NULL ,
							  `post-office-box` VARCHAR(45) NULL ,
							  `extended-address` TEXT NULL ,
							  `street-address` TEXT NULL ,
							  `locality` VARCHAR(100) NULL ,
							  `region` VARCHAR(45) NULL ,
							  `postal-code` INT NULL ,
							  `country-name` VARCHAR(90) NULL ,
							  PRIMARY KEY (`idhresume_adr`, `hresume_vcard_idhresume_vcard`) ,
							  INDEX `fk_hresume_adr_hresume_vcard1` (`hresume_vcard_idhresume_vcard` ASC) ,
							  CONSTRAINT `fk_hresume_adr_hresume_vcard1`
							    FOREIGN KEY (`hresume_vcard_idhresume_vcard` )
							    REFERENCES `{$this->db['vcard']}` (`idhresume_vcard` )
							    ON DELETE NO ACTION
							    ON UPDATE NO ACTION
  						 );" ); 			      
     
     		 # dhr_url 			
     $wpdb->query(  "CREATE TABLE {$this->db['url']} (
						  `idhresume_url` INT NOT NULL AUTO_INCREMENT,
						  `hresume_vcard_idhresume_vcard` INT NOT NULL ,
						  `url` VARCHAR(255) NULL ,
						  PRIMARY KEY (`idhresume_url`, `hresume_vcard_idhresume_vcard`) ,
						  INDEX `fk_hresume_url_hresume_vcard1` (`hresume_vcard_idhresume_vcard` ASC) ,
						  CONSTRAINT `fk_hresume_url_hresume_vcard1`
						    FOREIGN KEY (`hresume_vcard_idhresume_vcard` )
						    REFERENCES `{$this->db['vcard']}` (`idhresume_vcard` )
						    ON DELETE NO ACTION
						    ON UPDATE NO ACTION
  						 );" ); 	

          		 # dhr_tel 			
     $wpdb->query(  "CREATE TABLE {$this->db['tel']} (
						  `idhresume_tel` INT NOT NULL AUTO_INCREMENT,
						  `hresume_vcard_idhresume_vcard` INT NOT NULL ,
						  `tel` VARCHAR(20) NULL ,
						  `type` VARCHAR(15) NULL ,
						  PRIMARY KEY (`idhresume_tel`, `hresume_vcard_idhresume_vcard`) ,
						  INDEX `fk_hresume_tel_hresume_vcard1` (`hresume_vcard_idhresume_vcard` ASC) ,
						  CONSTRAINT `fk_hresume_tel_hresume_vcard1`
						    FOREIGN KEY (`hresume_vcard_idhresume_vcard` )
						    REFERENCES `{$this->db['vcard']}` (`idhresume_vcard` )
						    ON DELETE NO ACTION
						    ON UPDATE NO ACTION
  						 );" );
     
          		 # dhr_email 			
     $wpdb->query(  "CREATE TABLE {$this->db['email']} (
						  `idhresume_email` INT NOT NULL AUTO_INCREMENT,
						  `hresume_vcard_idhresume_vcard` INT NOT NULL ,
						  `email` VARCHAR(255) NULL ,
						  `type` VARCHAR(15) NULL ,
						  PRIMARY KEY (`idhresume_email`, `hresume_vcard_idhresume_vcard`) ,
						  INDEX `fk_hresume_email_hresume_vcard1` (`hresume_vcard_idhresume_vcard` ASC) ,
						  CONSTRAINT `fk_hresume_email_hresume_vcard1`
						    FOREIGN KEY (`hresume_vcard_idhresume_vcard` )
						    REFERENCES `{$this->db['vcard']}` (`idhresume_vcard` )
						    ON DELETE NO ACTION
						    ON UPDATE NO ACTION
  						 );" );
     
          		 # dhr_name 			
     $wpdb->query(  "CREATE TABLE {$this->db['name']} (
						  `idhresume_name` INT NOT NULL AUTO_INCREMENT,
						  `hresume_vcard_idhresume_vcard` INT NOT NULL ,
						  `family-name` VARCHAR(100) NULL ,
						  `given-name` VARCHAR(100) NULL ,
						  `additional-name` VARCHAR(100) NULL ,
						  `honorific-prefix` VARCHAR(30) NULL ,
						  `honorific-suffix` VARCHAR(30) NULL ,
						  PRIMARY KEY (`idhresume_name`, `hresume_vcard_idhresume_vcard`) ,
						  INDEX `fk_hresume_name_hresume_vcard1` (`hresume_vcard_idhresume_vcard` ASC) ,
						  CONSTRAINT `fk_hresume_name_hresume_vcard1`
						    FOREIGN KEY (`hresume_vcard_idhresume_vcard` )
						    REFERENCES `{$this->db['vcard']}` (`idhresume_vcard` )
						    ON DELETE NO ACTION
						    ON UPDATE NO ACTION
  						 );" );

               		 # dhr_org 			
     $wpdb->query(  "CREATE TABLE {$this->db['org']} (
						  `idhresume_org` INT NOT NULL AUTO_INCREMENT,
						  `hresume_vcard_idhresume_vcard` INT NOT NULL ,
						  `organization-name` VARCHAR(100) NULL ,
						  `organization-unit` VARCHAR(100) NULL ,
						  PRIMARY KEY (`idhresume_org`, `hresume_vcard_idhresume_vcard`) ,
						  INDEX `fk_hresume_org_hresume_vcard1` (`hresume_vcard_idhresume_vcard` ASC) ,
						  CONSTRAINT `fk_hresume_org_hresume_vcard1`
						    FOREIGN KEY (`hresume_vcard_idhresume_vcard` )
						    REFERENCES `{$this->db['vcard']}` (`idhresume_vcard` )
						    ON DELETE NO ACTION
						    ON UPDATE NO ACTION
  						 );" );
      
     add_option('dhr_version', $this->version, 'Installed version log');
      
   	  $this->installed = true;
    }
    //Create an option to backup display property
  	$options = get_option('DHResume_display_list');
    if ($options == FALSE) { 
    	$this->display_option_list = array(
      'name' => array (	  'family-name'	=> true,
					      'given-name'	=> true,
					      'additional-name'	=> false,  
					      'honorific-prefix'	=> false,
    					  'honorific-suffix'	=> false
    			),
      'info' => array ('last-update'	=> false),
      'job' => array ('title'	=> true,
				      'organization-name'	=> true,
				      'summary'	=> false,
    				  'period'	=> true),
      'educ' => array(  'summary'	=> true,
					      'organization-name'	=> true,
					      'period'	=> true)
    );
    add_option('DHResume_display_list', $this->display_option_list);  

 	}

  }                                                                                      
  
  /**
   * Called when plugin is deactivated 
   *
   *
   */
  function deactivate()
  {    
  }       
  
  /**
   * Uninstalls
   *
   *
   */
  function uninstall()
  {   
    global $wpdb;
       
    foreach($this->db as $table) 
      $wpdb->query("DROP TABLE {$table} ");
  }
  
/**
 * Called to create each menu
 */	
  function menu_admin() {
		add_menu_page(__('DHResume','dhresume'), __('DHResume','dhresume'), 'administrator', 'dhresume', array(&$this,'admin_menu_display'));
     	add_submenu_page('dhresume', __('Display Page','dhresume'), __('Display Page','dhresume'), 'administrator', 'dhresume', array(&$this,'admin_menu_display'));
      	add_submenu_page( 'dhresume', 'Add Resume', 'Add Resume', 'administrator', 'dhresume-add-resume', array(&$this,'add_resume_from_url'));
		add_submenu_page( 'dhresume', 'List Resume', 'List Resume', 'administrator', 'dhresume-list-resume', array(&$this,'list_resume'));
		add_submenu_page( 'dhresume', 'Infos', 'Infos', 'administrator', 'dhresume-infos', array(&$this,'infos'));
    }
    

/*
 * This hook is used to inject into the HTML header CSS when viewing the
 * generated hResume page.
 * Thanks to My hResume plugin
 */
function hResume_WP_wp_head()
{
    global $hResume_Options;
    
    /* only add the css if the user is viewing the resume page */
    //if ($_GET['page_id'] == $hResume_Options['page_id'])
    //{
        /* stop that stupid wpautop function from f'in up the data */
        remove_filter('the_content', 'wpautop');

	echo '<style type="text/css">
			
			.hresume
			{
			    width: 100%;
			    padding: 10px;
			}
			
			.hresume abbr
			{
			    border: none;
			}
			
			.hresume .org 
			{
				font-weight: 600;
			}
			
			.hresume address
			{
			    font-style: normal;
			}
			
			.hresume div.hr
			{
			    display: block;
			    height: 30;
			    background:transparent url(/wp-content/themes/colorpaper/images/post.jpg) no-repeat scroll left bottom;
			}
			
			.hresume divss{
				
				margin:0pt 0pt 25px;
				padding:0pt 0pt 35px;
			}
			
			.hresume .fn
			{
				font-weight: bold;
				font-size: 1.5em;
			}
			
			.hresume .wrapper
			{
				background:transparent url(/wp-content/themes/colorpaper/images/post.jpg) no-repeat scroll left bottom;
				padding: 0pt 0pt 25px;
				margin: 0pt 0pt 25px;
			}
			
			.hresume h2
			{
				font-size: 1.2em;
				padding: 0 0 5px 0;
				margin: 0;
			}
			
			.hresume .education, .hresume .accreditation
			{
			    margin: 10px 0 10px 20px;
			    padding: 1px;
			}
			
			.hresume .experience
			{
			    margin: 10px 0 30px 20px;
			    padding: 1px;
			}
			
			.hresume .summary, .hresume .tags
			{
			    margin: 10px 0 20px 20px;
			    padding: 1px;
			}
			
			.hresume .education .htitle, .hresume .experience .htitle
			{
			    float: left;
				font-size: 1em;
			}
			.hresume. 
			{
				visibility: visible;
			}
			.hresume .htitle .include
			{
			    display: none;
				height: 0px;
				width: 0px;
			}
			
			.hresume .education .date_duration, .hresume .experience .date_duration, .hresume .experience .location
			{
				float: right;
				font-size: 1em;
				font-style: italic;
			}
			
			.hresume .education .summary
			{
			    margin: 0;
			}
			
			.hresume .tags ul
			{
			    list-style: none;
			    margin: 0 0 0 125px;
			    padding: 0;
			}
			
			.hresume .tags ul li
			{
			    display: inline;
			}
			
			.hresume .tags h5 {
				float:left; 
				width: 120px;
				font-size: 9pt;
				font-weight: 300;
				line-height: 25px;
			}
			
			div.tagscontent {
				border: 1px dashed #dadbcd;
				background: #eceddd;
				padding: 5px;
				margin: 0px 15px 0px 15px;
			}
			
			div.detail { display: none; }
			
			.tags a { color: #C27127 ! important; text-decoration: underline ! important; line-height: 25px ! important;}
			.tags a:hover { text-decoration: none ! important; }
			.tags a:visited { color: #C27127 ! important; text-decoration: underline ! important; }
			
			.tags li { display: inline; }
			.tags span { position: absolute; left: -999px; width: 990px; }
			
			.tags .novice { font-size: 1em; }
			.tags .rookie { font-size: 1.3em; }
			.tags .competent { font-size: 1.6em; }
			.tags .skilled { font-size: 1.9em; }
			.tags .expert { font-size: 2.2em; }
			
			</style>
			<script>';
			echo "jQuery('div.tags').ready(function()
			{
				jQuery('div.tags a.skill').click(function()
				{
					var skill = this.href.substring(this.href.lastIndexOf('#')+1, this.href.length);
					jQuery('div.tagscontent div#details').fadeOut(\"fast\", function() {
			
						if (jQuery('div.tagscontent div.' + skill).length > 0) {
							jQuery('div.tagscontent div#details').html(
								jQuery('div.tagscontent div.' + skill).html()
							).fadeIn();
						}
						else {
							jQuery('div.tagscontent div#details').html(\"No content available\").fadeIn();
						}
			
					});
			
					return false;
				});
			
			});
			</script>";
			        
			    // }
}
    
    
 /**
   * Verify if a resume exist in the database with his URL
   * 
   * @param $url
   * @return true if exist else false
   */
  function exist_resume($url) {
  	
  	global $wpdb;
  	
  	$result = $wpdb->get_row("SELECT idhresume_index FROM  {$this->db['index']} WHERE  url =  '{$url}'");
  	if($result->idhresume_index != null) return $result->idhresume_index;
  	else return false;
  	
  }
  
  /**
   * Verify if a resume with his hash is uptodate or no
   * @param $hash <p>hash sha1 of the new resume</p>
   * @param $url <p>Url of the resume</p>
   */
   function is_uptodate($hash, $url) {
  	
  	global $wpdb;
  	
  	$result = $wpdb->get_row("SELECT hash FROM  {$this->db['index']} WHERE  url =  '{$url}'");
  	if($result->hash != $hash) return false;
  	else return true;
  	
  }
  
  /**
   * Remove data of a resume with his index ID
   * @param $id
   * @return true if data were removed else false
   */
  function remove_resume($id) {
  	global $wpdb;
  	
  	$queryContact = "SELECT `{$this->db['contact']}`.`hresume_vcard_idhresume_vcard` contact_vcard
	FROM `{$this->db['contact']}`
	WHERE `{$this->db['contact']}`.`hresume_index_idhresume_index` = ".$id.";";
  	$resultsContact = $wpdb->get_results($queryContact, ARRAY_A );
  	if(is_array($resultsContact))
  	foreach($resultsContact as $vcard)
  		$vcardList[] = $vcard['contact_vcard'];
  	
  	$queryEduc = "SELECT `{$this->db['education']}`.`hresume_vcard_idhresume_vcard` educ_vcard, `{$this->db['education']}`.`hresume_vevent_idhresume_vevent` educ_event
	FROM `{$this->db['education']}`
	WHERE `{$this->db['education']}`.`hresume_index_idhresume_index` = ".$id.";";
	$resultsEduc = $wpdb->get_results($queryEduc, ARRAY_A );
	if(is_array($resultsEduc))
	foreach($resultsEduc as $result)
  		$vcardList[] = $result['educ_vcard'];
  	
	
	$queryExp = "SELECT `{$this->db['experience']}`.`hresume_vcard_idhresume_vcard` exp_vcard, `{$this->db['experience']}`.`hresume_vevent_idhresume_vevent` exp_event
	FROM `{$this->db['experience']}`
	WHERE `{$this->db['experience']}`.`hresume_index_idhresume_index` = ".$id.";";
  	$resultsExp = $wpdb->get_results($queryExp, ARRAY_A );
  	if(is_array($resultsExp))
  	foreach($resultsExp as $result) 
  		$vcardList[] = $result['exp_vcard'];
  	
	
	$numVcard = count($vcardList); $i=1;
	if(is_array($vcardList))
	foreach($vcardList as $id_vcard) {
		$whereVcard .= "`hresume_vcard_idhresume_vcard` = '".$id_vcard."' ";
		if($i<$numVcard) {$whereVcard .= "OR ";}
		$i++;
	}
	
	if(!empty($whereVcard)) {
		$query = "DELETE FROM {$this->db['adr']} WHERE ".$whereVcard.";";
		$delete += $wpdb->query($query);
		$query = "DELETE FROM {$this->db['email']} WHERE ".$whereVcard.";";
		$delete += $wpdb->query($query);
		$query = "DELETE FROM {$this->db['name']} WHERE ".$whereVcard.";";
		$delete += $wpdb->query($query);
		$query = "DELETE FROM {$this->db['org']} WHERE ".$whereVcard.";";
		$delete += $wpdb->query($query);
		$query = "DELETE FROM {$this->db['tel']} WHERE ".$whereVcard.";";
		$delete += $wpdb->query($query);
		$query = "DELETE FROM {$this->db['title']} WHERE ".$whereVcard.";";
		$delete += $wpdb->query($query);
		$query = "DELETE FROM {$this->db['url']} WHERE ".$whereVcard.";";
		$delete += $wpdb->query($query);
	}
	$query = "DELETE FROM {$this->db['vevent']} WHERE `hresume_index_idhresume_index` = '".$id."';";
	$delete += $wpdb->query($query);
	$query = "DELETE FROM {$this->db['vcard']} WHERE `hresume_index_idhresume_index` = '".$id."';";
	$delete += $wpdb->query($query);
	$query = "DELETE FROM {$this->db['education']} WHERE `hresume_index_idhresume_index` = '".$id."';";
	$delete += $wpdb->query($query);
	$query = "DELETE FROM {$this->db['experience']} WHERE `hresume_index_idhresume_index` = '".$id."';";
	$delete += $wpdb->query($query);
	$query = "DELETE FROM {$this->db['contact']}  WHERE `hresume_index_idhresume_index` = '".$id."';";
	$delete += $wpdb->query($query);
	$delete += $wpdb->query("DELETE FROM {$this->db['index']} WHERE `idhresume_index` = '".$id."'");
  	if($delete > 0) return true;
  	else return false;
  }
  
  /**
   * Insert a resume in the directory
   * @param $url <p>URL of the resume</p>
   * @return true if resume is added else false
   */
  function exec_resume($url) {
  	
  	global $wpdb;	
  	require_once(XMFP_INCLUDE_PATH . 'class.Xmf_Parser.php');
		
  		$xmfp = Xmf_Parser::create_by_URI($mF_roots, $url); //Parsing of the resume
		$results = $xmfp->get_parsed_mfs();
		
		if(is_array($results['hresume']))
		 foreach($results['hresume'] as $resume) {
			$hash = sha1(serialize($resume));
			$exist = $this->exist_resume($url);
			if($exist !== false && $this->is_uptodate($hash, $url)) {
				return false;
			} else {
				if($exist !== false) $this->remove_resume($exist);
				$wpdb->query( "INSERT INTO {$this->db['index']} ( `idhresume_index` ,`url` ,`last_update` ,`hash`)VALUES (NULL , '{$url}', NOW( ) ,  '{$hash}');" );
				$this->id_index = $wpdb->insert_id;
			}
			
			//Vcard creation
			$contact = $resume['contact'];
			$wpdb->insert( $this->db['vcard'], array( 'hresume_index_idhresume_index' => $this->id_index), array( '%d') );
			$this->id_vcard = $wpdb->insert_id;
			if(isset($resume["contact"]["vcard"])) $this->insert_vcard($resume["contact"]["vcard"]);
			$wpdb->insert( $this->db['contact'], array( 'hresume_index_idhresume_index' => $this->id_index, 'hresume_vcard_idhresume_vcard' => $this->id_vcard), array( '%d') );
			
			//Create each experience
			foreach($resume['experience'] as $experience) {
				$wpdb->insert( $this->db['vcard'], array( 'hresume_index_idhresume_index' => $this->id_index), array( '%d') );
				$this->id_vcard = $wpdb->insert_id;
				if(isset($experience["vcard"])) $this->insert_vcard($experience["vcard"]);
				if(isset($experience["vevent"])) $this->insert_vevent($experience["vevent"]);
				$wpdb->insert( $this->db['experience'], array( 'hresume_index_idhresume_index' => $this->id_index, 'hresume_vcard_idhresume_vcard' => $this->id_vcard, 'hresume_vevent_idhresume_vevent'=> $this->id_vevent), array( '%d') );
			}
			//Create each education
		 	foreach($resume['education'] as $education) {
		 		$wpdb->insert( $this->db['vcard'], array( 'hresume_index_idhresume_index' => $this->id_index), array( '%d') );
		 		$this->id_vcard = $wpdb->insert_id;
		 		if(isset($education["vcard"])) $this->insert_vcard($education["vcard"]);
				if(isset($education["vevent"])) $this->insert_vevent($education["vevent"]);
				$wpdb->insert( $this->db['education'], array( 'hresume_index_idhresume_index' => $this->id_index, 'hresume_vcard_idhresume_vcard' => $this->id_vcard, 'hresume_vevent_idhresume_vevent'=> $this->id_vevent), array( '%d') );
		 	}
		 	
		}
		 else return false;
  		return true;
  }
  

  /**
   * Create a vcard
   * @param $data
   */
  function insert_vcard($data) {
  	foreach($data as $name => $element) {
		$this->selector_vcard($name, $element);
	}
  }
  
  /**
   * Switch data to his insertion function in the database
   * @param $name <p>Name of the entry</p>
   * @param $data
   */
  function selector_vcard($name, $data) {
  	
  	switch($name) {
  		case "n": return $this->insert_vcard_name($data);
  		case "adr":return $this->insert_vcard_adr($data);
  		case "tel":return $this->insert_vcard_tel($data);
  		case "email":return $this->insert_vcard_email($data);
  		case "url":return $this->insert_vcard_url($data);
  		case "org":return $this->insert_vcard_org($data);
  		case "title":return $this->insert_vcard_title($data);
  		default:break;
  	}
  	
  }
  
  /**
   * Insert vcard name in the database
   * @param $data
   */
  function insert_vcard_name($data) {
 	global $wpdb;
 	$name['hresume_vcard_idhresume_vcard'] = $this->id_vcard; $type[] = '%d';
  	if(isset($data['family-name'][0])) { $name['family-name'] = $data['family-name'][0]; $type[] = '%s'; }
  	if(isset($data['given-name'][0])) { $name['given-name'] = $data['given-name'][0]; $type[] = '%s'; }
  	if(isset($data['additional-name'][0])) { $name['additional-name'] = $data['additional-name'][0]; $type[] = '%s'; }
  	if(isset($data['honorific-prefix'][0])) { $name['honorific-prefix'] = $data['honorific-prefix'][0]; $type[] = '%s'; }
  	if(isset($data['honorific-suffix'][0])) { $name['honorific-suffix'] = $data['honorific-suffix'][0]; $type[] = '%s'; }
  	$wpdb->insert( $this->db['name'], $name, $type );
  }
  
  /**
   * Insert vcard address in the database
   * @param $data
   */
  function insert_vcard_adr($data) {
  
  	global $wpdb;
 	foreach($data as $val) {
 		
 		$adr['hresume_vcard_idhresume_vcard'] = $this->id_vcard; $type[] = '%d';
	  	if(isset($val['post-office-box'])) { $adr['post-office-box'] = $val['post-office-box']; $type[] = '%s'; }
	  	if(isset($val['extended-address'][0])) { $adr['extended-address'] = $val['extended-address'][0]; $type[] = '%s'; }
	  	if(isset($val['street-address'][0])) { $adr['street-address'] = $val['street-address'][0]; $type[] = '%s'; }
	  	if(isset($val['locality'])) { $adr['locality'] = $val['locality']; $type[] = '%s'; }
	  	if(isset($val['region'])) { $adr['region'] = $val['region']; $type[] = '%s'; }
	  	if(isset($val['postal-code'])) { $adr['postal-code'] = $val['postal-code']; $type[] = '%d'; }
	  	if(isset($val['country-name'])) { $adr['country-name'] =  $val['country-name']; $type[] = '%s'; }
		$wpdb->insert( $this->db['adr'], $adr, $type );
 	}
  }
  
  /**
   * Insert vcard telephone in the database
   * @param $data
   */
  function insert_vcard_tel($data) {
  
  	global $wpdb;
  	foreach($data as $val) {
	 	$tel['hresume_vcard_idhresume_vcard'] = $this->id_vcard;  $type[] = '%d';
	  	if(isset($val['value'])) { $tel['tel'] = $val['value']; $type[] = '%s'; }
	  	if(isset($val['type'][0])) { $tel['type'] = $val['type'][0]; $type[] = '%s'; }
	  	$wpdb->insert( $this->db['tel'], $tel, $type );
	}
  	
  	
  }
  
  /**
   * Insert vcard email in the database
   * @param $data
   */
  function insert_vcard_email($data) {
  
    global $wpdb;
    foreach($data as $val) {
	 	$email['hresume_vcard_idhresume_vcard'] = $this->id_vcard;  $type[] = '%d';
	  	if(isset($val['email'])) { $email['email'] = $val['email']; $type[] = '%s'; }
	  	if(isset($val['type'][0])) { $email['type'] = $val['type'][0]; $type[] = '%s'; }
	  	$wpdb->insert( $this->db['email'], $email, $type );
    }
  }
  
  /**
   * Insert vcard URL in the database
   * @param $data
   */
  function insert_vcard_url($data) {
  
    global $wpdb;
    foreach($data as $val) {
	 	$url['hresume_vcard_idhresume_vcard'] = $this->id_vcard;  $type[] = '%d';
	  	if(isset($val)) { $url['url'] = $val; $type[] = '%s'; }
	  	$wpdb->insert( $this->db['url'], $url, $type );
    }
  }
  
  /**
   * Insert vcard organisation in the database
   * @param $data
   */
  function insert_vcard_org($data) {
  	
  	global $wpdb;
  	$org['hresume_vcard_idhresume_vcard'] = $this->id_vcard;  $type[] = '%d';
  	if(isset($data['organization-name'])) { $org['organization-name'] = $data['organization-name']; $type[] = '%s'; }
  	if(isset($data['organization-unit'])) { $org['organization-unit'] = $data['organization-unit']; $type[] = '%s'; }
  	$wpdb->insert( $this->db['org'], $org, $type );
  }
  
  /**
   * Insert vcard title in the database
   * @param $data
   */
  function insert_vcard_title($data) {
  	
  	global $wpdb;
	foreach($data as $val) {
		$title['hresume_vcard_idhresume_vcard'] = $this->id_vcard;  $type[] = '%d';
		if(isset($val)) { $title['title'] =  $val; $type[] = '%s'; }
		$wpdb->insert( $this->db['title'], $title, $type );
	}
  }
  
/**
 * Insert vevent in the database
 * @param unknown_type $data
 */
  function insert_vevent($data) {
  	global $wpdb;
 	$name['hresume_index_idhresume_index'] = $this->id_index;  $type[] = '%d';
  	if(isset($data['location'])) { $name['location'] = $data['location']; $type[] = '%s'; }
  	if(isset($data['dtstart'])) { $name['dtstart'] = $data['dtstart']; $type[] = '%s'; }
  	if(isset($data['dtend'])) { $name['dtend'] = $data['dtend']; $type[] = '%s'; }
  	if(isset($data['summary']) && isset($data['description'])) {
  		$name['summary'] = (strlen($data['summary']) > strlen($data['description'])) ? $data['summary'] : $data['description'];
  		$type[] = '%s'; 
  	}
  	else {
  		if(isset($data['summary'])) { $name['summary'] = $data['summary']; $type[] = '%s'; }
  	}
  	if(isset($data['url'])) { $name['url'] = $data['url']; $type[] = '%s'; }
  	$wpdb->insert( $this->db['vevent'], $name, $type );
  	$this->id_vevent = $wpdb->insert_id;
  }
  
  /**
   * Choose the good function to display information in the directory
   */
  function display_name_adr() {
  	if(isset($_GET['filter'])) $this->display_list(true, $_GET['filter']); //Call the simple list
  	elseif (isset($_GET['view']) && is_numeric($_GET['view'])) $this->display_resume($_GET['view']); //Call the resume display
  	elseif(isset($_POST['search'])) $this->display_list(false, NULL, $this->create_search_query($_POST['search'])); //Call the result of the searcher
  	else $this->display_list();
  }
  
  /**
   * Get each resume ID who contain a specific query 
   * @param $search
   * @return array of ID
   */
  function create_search_query($search) {
  	global $wpdb;
  	
  	$queryExp = "SELECT event.`hresume_index_idhresume_index` "
			."FROM (({$this->db['experience']} exp "
        	."LEFT OUTER JOIN {$this->db['org']} org "
        	."ON exp.`hresume_vcard_idhresume_vcard`= org.`hresume_vcard_idhresume_vcard`) "
          	."LEFT OUTER JOIN {$this->db['vevent']} event "
          	."ON exp.`hresume_vevent_idhresume_vevent` = event.`idhresume_vevent`) "
			."LEFT OUTER JOIN {$this->db['title']} title "
			."ON exp.`hresume_vcard_idhresume_vcard` = title.`hresume_vcard_idhresume_vcard` "
			."WHERE org.`organization-name` LIKE '%".$search."%' "
			."OR org.`organization-unit` LIKE '%".$search."%' "
			."OR event.`url` LIKE '%".$search."%' "
			."OR event.`location` LIKE '%".$search."%' "
			."OR event.`summary` LIKE '%".$search."%' "
			."OR title.`title` LIKE '%".$search."%' ";
			
			
	$resultsExp = $wpdb->get_results($queryExp, ARRAY_A );
	if(is_array($resultsExp))
	foreach($resultsExp as $res){
		$result[] = $res['hresume_index_idhresume_index'];
	}
	
	$queryEduc = "SELECT event.`hresume_index_idhresume_index` "
			."FROM   ({$this->db['education']} educ "
        	."LEFT OUTER JOIN {$this->db['org']} org "
        	."ON educ.`hresume_vcard_idhresume_vcard`= org.`hresume_vcard_idhresume_vcard`) "
			."LEFT OUTER JOIN {$this->db['vevent']} event "
			."ON educ.`hresume_vevent_idhresume_vevent` = event.`idhresume_vevent` "
			."WHERE org.`organization-name` LIKE '%".$search."%' "
			."OR org.`organization-unit` LIKE '%".$search."%' "
			."OR event.`url` LIKE '%".$search."%' "
			."OR event.`location` LIKE '%".$search."%' "
			."OR event.`summary` LIKE '%".$search."%' ";
	$resultsEduc = $wpdb->get_results($queryEduc, ARRAY_A );
  	if(is_array($resultsEduc))
	foreach($resultsEduc as $res){
		$result[] = $res['hresume_index_idhresume_index'];
	}
	
	$queryNameAdr = "SELECT ind.`idhresume_index` "
					."FROM {$this->db['adr']} adr, {$this->db['name']} name, {$this->db['index']} ind, {$this->db['vcard']} vcard "
					."WHERE  adr.`hresume_vcard_idhresume_vcard` = name.`hresume_vcard_idhresume_vcard` "
					."AND ind.`idhresume_index` = vcard.`hresume_index_idhresume_index` "
					."AND vcard.`idhresume_vcard` = name.`hresume_vcard_idhresume_vcard` "
					."AND ( "
					."adr.`post-office-box` LIKE '%".$search."%' "
					."OR adr.`extended-address` LIKE '%".$search."%' "
					."OR adr.`street-address` LIKE '%".$search."%' "
					."OR adr.`locality` LIKE '%".$search."%' "
					."OR adr.`region` LIKE '%".$search."%' "
					."OR adr.`postal-code` LIKE '%".$search."%' "
					."OR adr.`country-name` LIKE '%".$search."%' "
					."OR name.`family-name` LIKE '%".$search."%' "
					."OR name.`given-name` LIKE '%".$search."%' "
					."OR name.`additional-name` LIKE '%".$search."%' "
					."OR name.`honorific-prefix` LIKE '%".$search."%' "
					."OR name.`honorific-suffix` LIKE '%".$search."%' "
					.");";
	$resultsNameAdr = $wpdb->get_results($queryNameAdr, ARRAY_A );
  	if(is_array($resultsNameAdr))
	foreach($resultsNameAdr as $res){
		$result[] = $res['idhresume_index'];
	}
	
	$queryEmail = "SELECT ind.`idhresume_index` "
				."FROM {$this->db['email']} email, {$this->db['index']} ind, {$this->db['vcard']} vcard "
				."WHERE ind.`idhresume_index` = vcard.`hresume_index_idhresume_index` "
				."AND vcard.`idhresume_vcard` = email.`hresume_vcard_idhresume_vcard` "
				."AND email.`email` LIKE '%".$search."%' ";
	$resultsEmail = $wpdb->get_results($queryEmail, ARRAY_A );
  	if(is_array($resultsEmail))
	foreach($resultsEmail as $res){
		$result[] = $res['idhresume_index'];
	}
	
	$queryTel = "SELECT ind.`idhresume_index` "
				."FROM {$this->db['tel']} tel, {$this->db['index']} ind, {$this->db['vcard']} vcard "
				."WHERE ind.`idhresume_index` = vcard.`hresume_index_idhresume_index` "
				."AND vcard.`idhresume_vcard` = tel.`hresume_vcard_idhresume_vcard` "
				."AND tel.`tel` LIKE '%".$search."%'";
	$resultsTel = $wpdb->get_results($queryTel, ARRAY_A );
  	if(is_array($resultsTel))
	foreach($resultsTel as $res){
		$result[] = $res['idhresume_index'];
	}
	
	$queryURL = "SELECT ind.`idhresume_index` "
				."FROM {$this->db['url']} url, {$this->db['index']} ind, {$this->db['vcard']} vcard "
				."WHERE ind.`idhresume_index` = vcard.`hresume_index_idhresume_index` "
				."AND vcard.`idhresume_vcard` = url.`hresume_vcard_idhresume_vcard` "
				."AND url.`url` LIKE '%".$search."%'";
	$resultsURL = $wpdb->get_results($queryURL, ARRAY_A );
 	if(is_array($resultsURL))
	foreach($resultsURL as $res){
		$result[] = $res['idhresume_index'];
	}
	if(is_array($result))
	return array_unique($result);
	else return false;
	
  }
  
  /**
   * Display a list of resume
   * @param $search <p>To activate the filter by a letter</p>
   * @param $request <p>Letter selected</p>
   * @param $resultsID <p>To display results of a query</p>
   */
  function display_list($search = false, $request = NULL, $resultsID = NULL) {
  	global $wpdb;
  	
  	//Display the index
  	$lettre = 'A';
  	for($var = 0; $var < 26; $var++) {
  		echo "<a href=\"?".$this->remove_arg_from_query($_SERVER["REQUEST_URI"], array("filter", "view"))."&filter=".$lettre."\">".$lettre."</a>"
  		.(($var < 25) ? "-" : "");
  		$lettre++;
  	}
  	//Display the search form
  	$this->display_search_form();
	echo "<br/>";
	
	if(is_null($resultsID)) {
	  	$query = "SELECT ind.`idhresume_index`"
				." FROM {$this->db['name']} name, {$this->db['index']} ind, {$this->db['contact']} ctt "
				."WHERE ind.idhresume_index = ctt.hresume_index_idhresume_index "
				."AND ctt.hresume_vcard_idhresume_vcard=name.hresume_vcard_idhresume_vcard "
				.(($search === true) ? "AND name.`family-name` LIKE  '".$request."%'" : "").";";
				
	  	$results = $wpdb->get_results($query, ARRAY_A );
	  	if(is_array($results))
		foreach($results as $res){
			$resultsID[] = $res['idhresume_index'];
		}
  	}
  	//Display each result
  	if(is_array($resultsID))
  	foreach($resultsID as $id) {
  		
  		
  		$this->display_option_list = get_option('DHResume_display_list');
		foreach($this->display_option_list['name'] as $name => $value) {
		  			if($value === true) { $column[] = "name.`".$name."`"; }
		  			
		}
		if($this->display_option_list['info']['last-update'] === true)  $column[] = "ind.`last_update`";
		$column[] = "ind.`idhresume_index`";
		$string = @implode(",", $column);
  		$query = "SELECT ".$string
			." FROM {$this->db['name']} name, {$this->db['index']} ind, {$this->db['contact']} ctt "
			."WHERE ind.idhresume_index = ctt.hresume_index_idhresume_index "
			."AND ctt.hresume_vcard_idhresume_vcard=name.hresume_vcard_idhresume_vcard "
			."AND ind.idhresume_index='".$id."'";
			
		$resultName = $wpdb->get_results($query, ARRAY_A );
  		echo "<div class=\"hresume\" onmouseover=\"this.style.backgroundColor = '#FFFFF0'\" onmouseout=\"this.style.backgroundColor = ''\" onclick=\"location.href='?".$this->remove_arg_from_query($_SERVER["REQUEST_URI"], array("filter", "view"))."&view=".$id."';\">";
  			echo '<div class="contact vcard">';
	  		//Contact
	  			echo '<div class="fn n" id="j">'
	  		. (($this->display_option_list['name']['given-name'] === true) ? '<span class="given-name">'.$resultName[0]['given-name'].'</span>' : "")
	  		.(($this->display_option_list['name']['honorific-prefix'] === true) ? '<span class="honorific-prefix">'.$resultName[0]['honorific-prefix'].'</span>' : "")
	  		. (($this->display_option_list['name']['family-name'] === true) ? '<span class="family-name">'.$resultName[0]['family-name'].'</span>' : "")
	  		. (($this->display_option_list['name']['honorific-suffix'] === true) ? '<span class="honorific-suffix">'.$resultName[0]['honorific-suffix'].'</span>' : "")
	  		. (($this->display_option_list['name']['additional-name'] === true) ? '<span class="additional-name">'.$resultName[0]['additional-name'].'</span>' : "")
	  		
	  			.'</div>';
	  		echo '<div style="clear: both;">&nbsp;</div>' 
	  		.'</div>' ;
		
		echo '<h2>Professional Experience</h2>' 
		.'<div class="vcalendar">'; 
  		//Experience
  		if($this->display_option_list['job']['organization-name'] === true)  $exp[] = "org.`organization-name`";
		if($this->display_option_list['job']['summary'] === true)  $exp[] = "event.`summary`";
		if($this->display_option_list['job']['period'] === true)  $exp[] = "event.`dtstart`, event.`dtend`";
		if($this->display_option_list['job']['title'] === true)  $exp[] = "title.`title`";
		$string = @implode(",", $exp);
  		$query = "SELECT ".$string."
				FROM {$this->db['vevent']} event, {$this->db['experience']} exp, {$this->db['org']} org, {$this->db['title']} title
				WHERE exp.`hresume_index_idhresume_index` = ".$id."
				AND exp.`hresume_vevent_idhresume_vevent` = event.`idhresume_vevent`
				AND exp.`hresume_vcard_idhresume_vcard`= org.`hresume_vcard_idhresume_vcard`
				AND exp.`hresume_vcard_idhresume_vcard`= title.`hresume_vcard_idhresume_vcard`
				ORDER BY event.`dtend` LIMIT 1;";
  		$resultsEvent = $wpdb->get_results($query, ARRAY_A );
  		if(is_array($resultsEvent))
  		foreach($resultsEvent as $resultEvent) {
  			echo '<div class="experience vevent vcard">'
		.'<div class="htitle">'
		.'<object data="#j" class="include"></object>';
	  		echo (($this->display_option_list['job']['organization-name'] === true) ? '<span class="org">'.$resultEvent['organization-name'].'</span><br/>' : "")
	  		. (($this->display_option_list['job']['title'] === true) ? '<span class="title">'.$resultEvent['title'].'</span>' : "")
	  		.'</div>'
	  		. (($this->display_option_list['job']['period'] === true) ? '<div class="date_duration"><abbr class="dtstart" title="'.$resultEvent['dtstart'].'">'.$resultEvent['dtstart'].'</abbr>'." to <abbr class=\"dtend\" title=\"".$resultEvent['dtend']."\">".$resultEvent['dtend']."</abbr></div>" : "")
	  		
	  		.'<div style="clear: both"></div> '
	  		. (($this->display_option_list['job']['summary'] === true) ? '<div class="summary">'.$resultEvent['summary'].'</div>' : "")
	  		.'</div>';
  		}
  		echo "</div>";

  		echo '<div class="wrapper">' 
		.'<h2>Education &amp; Affiliations</h2>' 
		.'<div class="vcalendar">';
		
  		//Education
  		if($this->display_option_list['educ']['organization-name'] === true)  $event[] = "org.`organization-name`";
		if($this->display_option_list['educ']['summary'] === true)  $event[] = "event.`summary`";
		if($this->display_option_list['educ']['period'] === true)  $event[] = "event.`dtstart`, event.`dtend`";
		$string = @implode(",", $event);
		
		$query = "SELECT ".$string."
				FROM {$this->db['vevent']} event, {$this->db['education']} educ, {$this->db['org']} org
				WHERE educ.`hresume_index_idhresume_index` = ".$id."
				AND educ.`hresume_vevent_idhresume_vevent` = event.`idhresume_vevent`
				AND educ.`hresume_vcard_idhresume_vcard`= org.`hresume_vcard_idhresume_vcard`
				ORDER BY event.`dtend` LIMIT 1;";
  		$resultsEvent = $wpdb->get_results($query, ARRAY_A );
  		if(is_array($resultsEvent))
  		foreach($resultsEvent as $resultEvent) {
  			echo '<div class="education vevent vcard">'
			.'<div class="htitle"><!-- div float container -->'
			.(($this->display_option_list['educ']['summary'] === true) ? '<span class="summary">'.$resultEvent['summary'].'</span><br/>' : "")
	  		.(($this->display_option_list['educ']['organization-name'] === true) ? '<span class="org">'.$resultEvent['organization-name'].'</span>' : "")
	  		.'</div>'
	  		. (($this->display_option_list['educ']['period'] === true) ? '<div class="date_duration"><abbr class="dtstart" title="'.$resultEvent['dtstart'].'">'.$resultEvent['dtstart'].'</abbr> to <abbr class="dtend" title="'.$resultEvent['dtend'].'">'.$resultEvent['dtend'].'</abbr></div>' : "")
	  		
		.'</div>';
	  		
  		}
  		echo '</div></div>'
  		. (($this->display_option_list['info']['last-update'] === true) ? '<div align="right">Last update: '.$resultName[0]['last_update'].'</div>' : "")
  		.'</div>';
  		echo "<hr/>";
  	
  	}
 }
  
 /**
  * Display a complete resume with his ID
  * @param $id
  */
  function display_resume($id) {
  	global $wpdb;
  	if(!is_numeric($id)) return false;
  	
  	$query = "SELECT *
			FROM {$this->db['index']} ind, {$this->db['contact']} ctt
			WHERE ind.idhresume_index = ctt.hresume_index_idhresume_index
			AND ind.idhresume_index = ".$id.";";
  	$result = $wpdb->get_results($query, ARRAY_A );
  	$idContactVcard = $result[0]['hresume_vcard_idhresume_vcard'];
  	
  	
  	$queryNameAdr = "SELECT *
					FROM {$this->db['adr']} adr, {$this->db['name']} name
					WHERE  adr.`hresume_vcard_idhresume_vcard` = name.`hresume_vcard_idhresume_vcard`
					AND name.`hresume_vcard_idhresume_vcard` = ".$idContactVcard.";";
	
	$queryEmail = "SELECT *
					FROM {$this->db['email']} email
					WHERE  email.`hresume_vcard_idhresume_vcard` = ".$idContactVcard.";";
	
	$queryTel = "SELECT *
				FROM {$this->db['tel']} tel
				WHERE  tel.`hresume_vcard_idhresume_vcard` = ".$idContactVcard.";";
	
	$queryURL = "SELECT *
				FROM {$this->db['url']} url
				WHERE  url.`hresume_vcard_idhresume_vcard` = ".$idContactVcard.";";
  	echo "<a href=\"?".$this->remove_arg_from_query($_SERVER["REQUEST_URI"], array("filter", "view"))."\">Return to the directory</a>";
	echo '<div class="hresume">
			<div class="contact vcard">';
	
	 $resultNameAdr = $wpdb->get_results($queryNameAdr, ARRAY_A );
  	//Contact
  	echo '<div class="fn n" id="j">'
  	.((!empty($resultNameAdr[0]['given-name'])) ? '<span class="given-name">'.$resultNameAdr[0]['given-name'].'</span>' : "")
  	.((!empty($resultNameAdr[0]['honorific-prefix'])) ? '<span class="honorific-prefix">'.$resultNameAdr[0]['honorific-prefix'].'</span>' : "")
  	.((!empty($resultNameAdr[0]['family-name'])) ? '<span class="family-name">'.$resultNameAdr[0]['family-name'].'</span>' : "")
  	.((!empty($resultNameAdr[0]['honorific-suffix'])) ? '<span class="honorific-suffix">'.$resultNameAdr[0]['honorific-suffix'].'</span>' : "")
  	.((!empty($resultNameAdr[0]['additional-name'])) ? '<span class="additional-name">'.$resultNameAdr[0]['additional-name'].'</span>' : "")
  	.'</div>'
  	.'<div class="adr">'
  	.((!empty($resultNameAdr[0]['post-office-box'])) ? '<span class="post-office-box">'.$resultNameAdr[0]['post-office-box'].'</span><br/>' : "")
  	.((!empty($resultNameAdr[0]['street-address'])) ? '<span class="street-address">'.$resultNameAdr[0]['street-address'].'</span><br/>' : "")
  	.((!empty($resultNameAdr[0]['extended-address'])) ? '<span class="extended-address">'.$resultNameAdr[0]['extended-address'].'</span><br/>' : "")
  	.((!empty($resultNameAdr[0]['locality'])) ? '<span class="locality">'.$resultNameAdr[0]['locality'].'</span>,' : "")
  	.((!empty($resultNameAdr[0]['region'])) ? '<span class="region">'.$resultNameAdr[0]['region'].'</span>' : "")
  	.((!empty($resultNameAdr[0]['postal-code'])) ? '<span class="postal-code">'.$resultNameAdr[0]['postal-code'].'</span><br/>' : "")
  	.((!empty($resultNameAdr[0]['country-name'])) ? '<span class="country-name">'.$resultNameAdr[0]['country-name'].'</span>' : "")
	.'</div>'
	.'<div style="float: left; padding-right: 15px;"> ';
	$resultTel = $wpdb->get_results($queryTel, ARRAY_A );
	if(is_array($resultTel))
	foreach($resultTel as $tel) {
		echo '<span class="tel">'
		.((!empty($tel['tel'])) ? '<span class="value">'.$tel['tel'].'</span>' : "")
		.((!empty($tel['type'])) ? '(<span class="type">'.$tel['type'].'</span>)' : "")
		.'</span><br/>';
	}
	echo '</div>'
	.'<div style="float: right;margin-top:-60px;">';
	$resultEmail = $wpdb->get_results($queryEmail, ARRAY_A );
	if(is_array($resultEmail))
	foreach($resultEmail as $email) {
		echo '<span class="email">'
		.((!empty($email['email'])) ? '<a class="email" href="mailto:'.$email['email'].'">'.$email['email'].'</a></span>' : "")
		.((!empty($email['type'])) ? '(<span class="type">'.$email['type'].'</span>)' : "")
		.'</span><br/>';
	}
	$resultURL = $wpdb->get_results($queryURL, ARRAY_A );
 	if(is_array($resultURL))
	foreach($resultURL as $url) {
		preg_match("/^(http:\/\/)?([^\/]+)/i",$url['url'],$chaines);
		echo ((!empty($url['url'])) ? '<a class="url" href="'.$url['url'].'">'.$chaines[2].'</a><br/>' : "");
	}
	echo '</div>'
		.'<div style="clear: both;">&nbsp;</div>' 
		.'</div>' 
		.'<div class="wrapper">' 
		.'<h2>Professional Experience</h2>' 
		.'<div class="vcalendar">'; 	
		
	//Experience
  	$queryExp = "SELECT org.`organization-name`, org.`organization-unit`, event.`dtstart`, event.`dtend`, event.`summary`, event.`url`, event.`location`, title.`title`
				FROM (({$this->db['experience']} exp
        				LEFT OUTER JOIN {$this->db['org']} org
        				ON exp.`hresume_vcard_idhresume_vcard`= org.`hresume_vcard_idhresume_vcard`)
          					LEFT OUTER JOIN {$this->db['vevent']} event
          					ON exp.`hresume_vevent_idhresume_vevent` = event.`idhresume_vevent`)
								LEFT OUTER JOIN {$this->db['title']} title
								ON exp.`hresume_vcard_idhresume_vcard` = title.`hresume_vcard_idhresume_vcard`
			WHERE exp.`hresume_index_idhresume_index` = ".$id.";";
  	$resultsExp = $wpdb->get_results($queryExp, ARRAY_A );
  	if(is_array($resultsExp))
  	foreach($resultsExp as $exp) {
		echo '<div class="experience vevent vcard">'
		.'<div class="htitle">'
		.'<object data="#j" class="include"></object>'
		
		.((!empty($exp['organization-name'])) ? '<span class="org">'.$exp['organization-name'].'</span><br/>' : "")
		.((!empty($exp['title'])) ? '<span class="title">'.$exp['title'].'</span>' : "")
		.'</div>'
		.((!empty($exp['location'])) ? '<div class="location">'.$exp['location'].'</div><br/>' : "")
		.'<div class="date_duration">'
		.((!empty($exp['dtstart'])) ? '<abbr class="dtstart" title="'.$exp['dtstart'].'">'.$exp['dtstart'].'</abbr>' : "")
		.'&#8211;'
		.((!empty($exp['dtend'])) ? '<abbr class="dtend" title="'.$exp['dtend'].'">'.$exp['dtend'].'</abbr>' : "")
		.'</div>'
		.'<div style="clear: both"></div> '
		.((!empty($exp['summary'])) ? '<div class="summary">'.$exp['summary'].'</div>' : "")
		.'</div>';
	}
  	echo '</div>'
		.'</div>'
		.'<div class="wrapper">' 
		.'<h2>Education &amp; Affiliations</h2>' 
		.'<div class="vcalendar">';
  	
  	//Education
  	$queryEduc = "SELECT org.`organization-name`, org.`organization-unit`, event.`dtstart`, event.`dtend`, event.`summary`, event.`url`, event.`location`
			FROM   ({$this->db['education']} educ
        				LEFT OUTER JOIN {$this->db['org']} org
        				ON educ.`hresume_vcard_idhresume_vcard`= org.`hresume_vcard_idhresume_vcard`)
			LEFT OUTER JOIN {$this->db['vevent']} event
			ON educ.`hresume_vevent_idhresume_vevent` = event.`idhresume_vevent`
			WHERE educ.`hresume_index_idhresume_index` = ".$id.";";
	
  	$resultsEduc = $wpdb->get_results($queryEduc, ARRAY_A );
  	if(is_array($resultsEduc))
  	foreach($resultsEduc as $educ) {
		echo '<div class="education vevent vcard">'
		.'<div class="htitle">'
		.((!empty($educ['summary'])) ? '<span class="summary">'.$educ['summary'].'</span><br/>' : "")
		.((!empty($educ['organization-name'])) ? '<span class="org">'.$educ['organization-name'].'</span>' : "")
		.'</div>'
		.'<div class="date_duration">'
		.((!empty($educ['dtstart'])) ? '<abbr class="dtstart" title="'.$educ['dtstart'].'">'.$educ['dtstart'].'</abbr>' : "")
		.'&#8211;'
		.((!empty($educ['dtend'])) ? '<abbr class="dtend" title="'.$educ['dtend'].'">'.$educ['dtend'].'</abbr>' : "")
		.'</div>'
		.'<div style="clear: both"></div> '
		.'</div>';
	}
  	echo '</div>'
		.'</div>'
		.'</div>';
	
   }
  
	/**
	 * Display a search form
	 */
   function display_search_form() {
  	
  	echo "<form action=\"?".$this->remove_arg_from_query($_SERVER["REQUEST_URI"], array("filter", "view"))."\" method=\"POST\">"
  	."Search: <input type=\"text\" name=\"search\">"
  	. "<input type=\"submit\" value=\"Search\"></form>";
  	
  }
   
   
   /**
    * Display a form to choose wich option is display in a search result
    */
  function admin_menu_display() {
  	wp_enqueue_style('cssResume');
  	$this->display_option_list = get_option('DHResume_display_list');
  	if(isset($_POST['change_display'])) {
  		foreach($this->display_option_list as $name => $value) {
  			foreach($value as $nameType => $valueType) {
  					if(isset($_POST[$name])) 
	  				$exist = array_search($nameType, $_POST[$name]);
	  				$this->display_option_list[$name][$nameType] = (is_numeric($exist)) ?  true : false;
  			}
  		}
	  	update_option('DHResume_display_list', $this->display_option_list);
  	}
  	
  	echo "<form action=\"".$_SERVER["REQUEST_URI"]."\" method=\"POST\">"
  	
  	.'<table class="widefat" cellspacing="0">'
	.'<thead>' 
	.'<tr> '
		.'<th scope="col" class="manage-column check-column"><input type="checkbox" /></th> '
		.'<th scope="col" class="manage-column">Value</th> '
		.'<th scope="col" class="manage-column">Description</th> '
	.'</tr> '
	.'</thead> '
 
	.'<tfoot>' 
	.'<tr> '
		.'<th scope="col" class="manage-column check-column"><input type="checkbox" /></th>' 
		.'<th scope="col" class="manage-column">Value</th>' 
		.'<th scope="col" class="manage-column">Description</th> '
	.'</tr> '
	.'</tfoot> '
 
	.'<tbody class="plugins"> '
	
	.'<tr class="'.(($this->display_option_list['name']['family-name']) ? "active" : "inactive").'"> '
	  	."<th scope='row' class='check-column'><input type=\"checkbox\" name=\"name[]\" value=\"family-name\" ".(($this->display_option_list['name']['family-name']) ? "checked" : "")."></th>"
	  	.'<td class="plugin-title"><strong>family-name</strong></td>'
	  	.'<td class="desc"><p>Show the family name in the page result.</p></td>'
	  	.'</tr> '
  	.'<tr class="'.(($this->display_option_list['name']['given-name']) ? "active" : "inactive").'"> '
	  	. "<th scope='row' class='check-column'><input type=\"checkbox\" name=\"name[]\" value=\"given-name\" ".(($this->display_option_list['name']['given-name']) ? "checked" : "")."></th>"
	  	.'<td class="plugin-title"><strong>given-name</strong></td>'
	  	.'<td class="desc"><p>Show the given name in the page result.</p></td>'
	  	.'</tr> '
  	.'<tr class="'.(($this->display_option_list['name']['additional-name']) ? "active" : "inactive").'"> '
	  	. "<th scope='row' class='check-column'><input type=\"checkbox\" name=\"name[]\" value=\"additional-name\" ".(($this->display_option_list['name']['additional-name']) ? "checked" : "")."></th>"
	  	.'<td class="plugin-title"><strong>additional-name</strong></td>'
	  	.'<td class="desc"><p>Show the additional name in the page result.</p></td>'
	  	.'</tr> '
  	.'<tr class="'.(($this->display_option_list['name']['honorific-prefix']) ? "active" : "inactive").'"> '
	  	. "<th scope='row' class='check-column'><input type=\"checkbox\" name=\"name[]\" value=\"honorific-prefix\" ".(($this->display_option_list['name']['honorific-prefix']) ? "checked" : "")."></th>"
	  	.'<td class="plugin-title"><strong>honorific-prefix</strong></td>'
	  	.'<td class="desc"><p>Show the honorific prefix in the page result.</p></td>'
	  	.'</tr> '
  	.'<tr class="'.(($this->display_option_list['name']['honorific-suffix']) ? "active" : "inactive").'"> '
	  	. "<th scope='row' class='check-column'><input type=\"checkbox\" name=\"name[]\" value=\"honorific-suffix\" ".(($this->display_option_list['name']['honorific-suffix']) ? "checked" : "")."></th>"
	  	.'<td class="plugin-title"><strong>honorific-suffix</strong></td>'
	  	.'<td class="desc"><p>Show the honorific suffix in the page result.</p></td>'
	  	.'</tr> '
  	.'<tr class="'.(($this->display_option_list['info']['last-update']) ? "active" : "inactive").'"> '
	  	. "<th scope='row' class='check-column'><input type=\"checkbox\" name=\"info[]\" value=\"last-update\" ".(($this->display_option_list['info']['last-update']) ? "checked" : "")."></th>"
	  	.'<td class="plugin-title"><strong>Last update</strong></td>'
	  	.'<td class="desc"><p>Show the last update date in the page result.</p></td>'
	  	.'</tr> '
  	.'<tr class="'.(($this->display_option_list['job']['title']) ? "active" : "inactive").'"> '
	  	. "<th scope='row' class='check-column'><input type=\"checkbox\" name=\"job[]\" value=\"title\" ".(($this->display_option_list['job']['title']) ? "checked" : "")."></th>"
	  	.'<td class="plugin-title"><strong>Last Job title</strong></td>'
	  	.'<td class="desc"><p>Show the last job title in the page result.</p></td>'
	  	.'</tr> '
  	.'<tr class="'.(($this->display_option_list['job']['organization-name']) ? "active" : "inactive").'"> '
	  	. "<th scope='row' class='check-column'><input type=\"checkbox\" name=\"job[]\" value=\"organization-name\" ".(($this->display_option_list['job']['organization-name']) ? "checked" : "")."></th>"
	  	.'<td class="plugin-title"><strong>Last employer name</strong></td>'
	  	.'<td class="desc"><p>Show the last employer name in the page result.</p></td>'
	  	.'</tr> '
  	.'<tr class="'.(($this->display_option_list['job']['summary']) ? "active" : "inactive").'"> '
	  	. "<th scope='row' class='check-column'><input type=\"checkbox\" name=\"job[]\" value=\"summary\" ".(($this->display_option_list['job']['summary']) ? "checked" : "")."></th>"
	  	.'<td class="plugin-title"><strong>Last job description</strong></td>'
	  	.'<td class="desc"><p>Show the last job description in the page result.</p></td>'
	  	.'</tr> '
  	.'<tr class="'.(($this->display_option_list['job']['period']) ? "active" : "inactive").'"> '
	  	. "<th scope='row' class='check-column'><input type=\"checkbox\" name=\"job[]\" value=\"period\" ".(($this->display_option_list['job']['period']) ? "checked" : "")."></th>"
	  	.'<td class="plugin-title"><strong>Last job period</strong></td>'
	  	.'<td class="desc"><p>Show the last job period in the page result.</p></td>'
	  	.'</tr> '
  	.'<tr class="'.(($this->display_option_list['educ']['summary']) ? "active" : "inactive").'"> '
	  	. "<th scope='row' class='check-column'><input type=\"checkbox\" name=\"educ[]\" value=\"summary\" ".(($this->display_option_list['educ']['summary']) ? "checked" : "")."></th>"
	  	.'<td class="plugin-title"><strong>Last diploma title</strong></td>'
	  	.'<td class="desc"><p>Show the last diploma title in the page result.</p></td>'
	  	.'</tr> '
  	.'<tr class="'.(($this->display_option_list['educ']['organization-name']) ? "active" : "inactive").'"> '
	  	. "<th scope='row' class='check-column'><input type=\"checkbox\" name=\"educ[]\" value=\"organization-name\" ".(($this->display_option_list['educ']['organization-name']) ? "checked" : "")."></th>"
	  	.'<td class="plugin-title"><strong>Last school name</strong></td>'
	  	.'<td class="desc"><p>Show the last school name in the page result.</p></td>'
	  	.'</tr> '
  	.'<tr class="'.(($this->display_option_list['educ']['period']) ? "active" : "inactive").'"> '
	  	. "<th scope='row' class='check-column'><input type=\"checkbox\" name=\"educ[]\" value=\"period\" ".(($this->display_option_list['educ']['period']) ? "checked" : "")."></th>"
		.'<td class="plugin-title"><strong>Last school period</strong></td>'
	  	.'<td class="desc"><p>Show the last school period in the page result.</p></td>'
	  	.'</tr> '
	.'</tbody>'
	.'</table>'
  	. "<input type=\"hidden\" name=\"change_display\" value=\"1\"><input type=\"submit\" value=\"Update !\"></form>";
  }
  
  
  /**
   * Permit to add a resume from an URL
   */
  function add_resume_from_url() {
  	if(isset($_POST['url'])) {
  		echo ($this->exec_resume($_POST['url'])) ? '<div class="updated fade">The resume is added !</div>' : '<div class="updated fade">An error is occured.</div>';
  	}
  	 
  	echo '<form action="'.$_SERVER["REQUEST_URI"].'" method="POST">'
		  	.'<table class="widefat" cellspacing="0">'
				.'<thead>' 
					.'<tr> '
						.'<th scope="col" class="manage-column">Add a resume from an URL</th> '
					.'</tr> '
				.'</thead> '
			 	.'<tbody class="plugins"> '
					.'<tr class="'.(($this->display_option_list['name']['family-name']) ? "active" : "inactive").'"> '
					  	.'<td class="desc">'
					  		.'<p><label for="resume_url">URL:</label> <input type="text" name="url" id="resume_url" class="code" value="" /><input type="submit" class="button tagadd" value="Add" tabindex="3" /><br /></p> '
					  	.'</td>'
					.'</tr> '
			  	.'</tbody>'
		  	.'</table>'
		  .'</form>';  
	}
  
	/**
	 * List each resume in the directory 
	 * Permit to delete or update a resume
	 */
  function list_resume() {
  	global $wpdb;
  	
  	if(isset($_GET['idresume']) && is_numeric($_GET['idresume'])) {
  		echo ($this->remove_resume($_GET['idresume']) === true) ? '<div class="updated fade">The resume is deleted !</div>' : '<div class="updated fade">An error is occured.</div>';
  		
  	}elseif(isset($_GET['refresh'])) 
  	echo ($this->exec_resume($_GET['refresh']) === true) ? '<div class="updated fade">The resume is now up to date !</div>' : '<div class="updated fade">The resume is already up to date </div>';
  	
  	$query = "SELECT ind.idhresume_index id, name.`family-name`, name.`given-name`, ind.`url`, ind.`last_update`
				 FROM {$this->db['name']} name, {$this->db['index']} ind, {$this->db['contact']} ctt
				WHERE ind.idhresume_index = ctt.hresume_index_idhresume_index
				AND ctt.hresume_vcard_idhresume_vcard=name.hresume_vcard_idhresume_vcard";
  	$results = $wpdb->get_results($query, ARRAY_A );
  	
  	echo '<table class="widefat" cellspacing="0">'
  		.'<thead>'
  		.'<tr>'
  		.'<th scope="col" class="manage-column">Given Name</th>'
  		.'<th scope="col" class="manage-column">Family Name</th>'
  		.'<th scope="col" class="manage-column">URL Hresume</th>'
  		.'<th scope="col" class="manage-column">Last Update</th>'
  		.'<th scope="col" class="manage-column">Action</th>'
  		
  		.'</tr>'
  		.'</thead>';  
  	if(is_array($results))
  	foreach($results as $res) {
	  echo '<tr>'
	  	.'<td>'
	  	.$res['given-name']
	  	.'</td>'
	  	.'<td>'
	  	.$res['family-name']
	  	.'</td>'
	  	.'<td>'
	  	.'<a href="'.$res['url'].'">'.$res['url'].'</a>'
	  	.'</td>'
	  	.'<td>'
	  	.$res['last_update']
	  	.'</td>'
	  	.'<td>'
	  	.'<a href="'.$_SERVER["REQUEST_URI"].'&idresume='.$res['id'].'">Delete</a> - '
	  	.'<a href="'.$_SERVER["REQUEST_URI"].'&refresh='.$res['url'].'">Update</a>'
	  	.'</td>'
	  .'</tr>';
	  }
	 else echo '<tr><td colspan="5"><p align="center">There is no resume in the database</p></td></tr>';
	 echo "</table>";
	
  }
  
  /**
   * Display infos about the plugin
   */
  function infos() {
  	
  	$siteurl = get_option('siteurl');
  	 
  	echo '<table class="widefat" cellspacing="0">'
				.'<thead>' 
					.'<tr> '
						.'<th scope="col" class="manage-column"></th> '
					.'</tr> '
				.'</thead> '
			 	.'<tbody class="plugins"> '
					.'<tr> '
					  	.'<td class="desc">'
					  		.'<p>The URL to the XML-RPC service is: '.$siteurl.'/wp-content/plugins/DHResume/xmlrpcDHResume.php</p>'
					  		.'<p>To create a page with the hResume Directory, use the shorttag [dhr_display] and publish it !</p>'
					  	.'</td>'
					.'</tr> '
			  	.'</tbody>'
		  	.'</table>';  
	}
  
 /***
  * Thanks to need_sunny@yahoo.com
  * http://fr2.php.net/manual/fr/function.parse-url.php#95304
  */
  function remove_arg_from_query($var, $remove)
  {
  /**
   *  Use this function to parse out the query array element from
   *  the output of parse_url().
   */
  $var  = parse_url($var, PHP_URL_QUERY);
  $var  = html_entity_decode($var);
  $var  = explode('&', $var);
  $arr  = array();

  foreach($var as $val)
   {
    $x          = explode('=', $val);
    $arr[$x[0]] = $x[1];
   }
  unset($val, $x, $var);
  foreach($remove as $arg) {
  	unset($arr[$arg]);
  }
  
  return http_build_query($arr);
 }
 

}        

$dhresume = & new DHResume();