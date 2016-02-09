<?php
/*
	Plugin Name: NP_MobileDetect
	
	Author URI: http://www.yellownote.nl
    Copyright 2011  Cris van Geel  (email : cm.v.geel@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
	
	Special thanks to ftruscot for providing the additional code for overriding the default SKIN !!
*/



class NP_MobileDetect extends NucleusPlugin
{

	function getName() 			{ return 'Mobile Detect';	}
	function getAuthor()		{ return 'Cris van Geel';	}
	function getURL()			{ return 'http://www.yellownote.nl/';	}
	function getVersion()		{ return '1.1';}
	function getDescription()	{ return 'Detects mobile user agents and automatically select a pre-defined skin for these users. You can also choose to redirect the user to a new URL. This can be done for WAP/WML only and MOBILE only devices.';	}
	


	
	function init() {
		    
			$this->userAgent = $_SERVER['HTTP_USER_AGENT'];
			$this->accept    = $_SERVER['HTTP_ACCEPT'];
			
			$this->isMobile = FALSE;	
			$this->acceptWAP = FALSE;	
			$this->acceptHTML = FALSE;
	
	}
	
	function install() 	{
	
	$this->createOption('MD_wapenabled', 'Act on WAP/WML clients (Usualy old telephones)', 'yesno', 'yes');
	$this->createOption('wapskin', 'Name of skin to use for WAP/WML clients', 'text', '');
	$this->createOption('wapurl', 'OR full URL (http://etcetc) for redirecting WAP/WML clients (this will overrule the WAP Skin selection!! Leave empty if unused)', 'text', '');
    $this->createOption('MD_mobileenabled', 'Act on mobile clients that support HTML (Iphone, PDAs etc)', 'yesno', 'yes');
	$this->createOption('mobileskin', 'Name of skin to use for Mobile clients', 'text', ''); 
	$this->createOption('mobileurl', 'OR full URL (http://etcetc) for redirecting Mobile clients (this will overrule the mobile skin selection!! Leave empty if unused)', 'text', '');
	
	}
	
	
	
	function unInstall() {
	}
	
	
	function getEventList() {
		return array('InitSkinParse');
	}
	
	function event_InitSkinParse(&$data) {
	
		global $skinid;
		
	
		$this->checkHTML();
		$this->checkWAP();
		$this->checkDevice();

		/* 
		Device accepts WAP 	: $this->acceptWAP == TRUE
		Device accepts HTML : $this->acceptHTML == TRUE
		Device is mobile 	: $this->isMobile = TRUE
		*/


		if ($this->getOption('MD_wapenabled') == "yes") {
			if ($this->acceptHTML && $this->acceptWAP == 1 && $this->isMobile ==1 && $this->getOption('wapurl') <> '') {
			
					/* Redirect to WAP Compatible version of Blog) */
					$url = 'Location: '.$this->getOption('wapurl');
					header($url); 
					exit;
			}
			
			
			 // Else force WAP Skin (Maybe headers need to be adjusted .. not sure....need testing)
			elseif  ($this->acceptHTML && $this->acceptWAP == 1 && $this->isMobile ==1 && $this->getOption('wapurl') == '') {
			
					if (SKIN::exists($this->getOption('wapskin'))) {
						$newskin = SKIN::createFromName($this->getOption('wapskin'));
						$newskinid = $newskin->id;
						$data['skin']->id = $newskin->id ;
						$data['skin']->name = $newskin->name;
						$data['skin']->description= $newskin->description;
						$data['skin']->contentType = $newskin->contentType;
						$data['skin']->includeMode = $newskin->includeMode;
						$data['skin']->includePrefix = $newskin->includePrefix;
					} 
			}
		}
		
	
		if ($this->getOption('MD_mobileenabled') == "yes") {
		
				/* Redirect to HTML Compatible version of Blog optimzed for mobile use) */
				if ($this->acceptHTML==1  && $this->isMobile==1 && $this->getOption('mobileurl') <> '') 
				{
					$url = 'Location: '.$this->getOption('mobileurl');
					header($url); 
					exit;
				}
				// Else force Mobile SKin
			
				elseif ($this->acceptHTML==1  && $this->isMobile==1 && $this->getOption('mobileurl') == '') {
			
					if (SKIN::exists($this->getOption('mobileskin'))) {
						$newskin = SKIN::createFromName($this->getOption('mobileskin'));
						$newskinid = $newskin->id;
						$data['skin']->id = $newskin->id ;
						$data['skin']->name = $newskin->name;
						$data['skin']->description= $newskin->description;
						$data['skin']->contentType = $newskin->contentType;
						$data['skin']->includeMode = $newskin->includeMode;
						$data['skin']->includePrefix = $newskin->includePrefix;
					} 
			
				}
		}

		
		/* Else just parse the blog as usual */	

	}
	
	function checkDevice() {
	 
		$mobiledevices 	= array("android"       => "android",
								"blackberry"    => "blackberry",
								"iphone"        => "(iphone|ipod)",
								"opera"         => "opera mini",
								"palm"          => "(avantgo|blazer|elaine|hiptop|palm|plucker|xiino)",
								"windows"       => "windows ce; (iemobile|ppc|smartphone)",
								"generic"       => "(kindle|mobile|mmp|midp|o2|pda|pocket|psp|symbian|smartphone|treo|up.browser|up.link|vodafone|wap)"); 
	
		foreach ($mobiledevices as $device => $search) {
	
			if (preg_match("/" . $mobiledevices[$device] . "/i", $this->userAgent)) { $this->isMobile = TRUE; }
		}
	
		
	}
	
	function checkWAP() {
				
			if (isset($_SERVER['HTTP_X_WAP_PROFILE'])|| isset($_SERVER['HTTP_PROFILE'])) 
			{
				$this->isMobile = true;	
				$this->acceptWAP = true;	
			} 
			
			elseif (strpos($this->accept,'text/vnd.wap.wml') > 0 || strpos($this->accept,'application/vnd.wap.xhtml+xml') > 0) 
			{
				$this->isMobile = true;
				$this->acceptWAP = true;	
			} 
	}
	
	
		function checkHTML() {
				
			if (strpos($this->accept,'text/html') > 0 || strpos($this->accept,'application/xhtml+xml') > 0) 
			{
				$this->acceptHTML = true;
			
			} 
		}
	
}

	

	


?>