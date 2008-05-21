<?php
/* vim: set noai expandtab tabstop=4 softtabstop=4 shiftwidth=4: */
/**
 * System_Daemon turns PHP-CLI scripts into daemons.
 * 
 * PHP version 5
 *
 * @category  System
 * @package   System_Daemon
 * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
 * @copyright 2008 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
 * @version   SVN: Release: $Id$
 * @link      http://trac.plutonia.nl/projects/system_daemon
 */

/**
 * A System_Daemon_OS driver Template for all Operating Systems
 *
 * @category  System
 * @package   System_Daemon
 * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
 * @copyright 2008 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
 * @version   SVN: Release: $Id$
 * @link      http://trac.plutonia.nl/projects/system_daemon
 * 
 */
class System_Daemon_OS_Common
{

    /**
     * Holds errors
     *
     * @var array
     */
    public $errors = array();
    
    /**
     * Array that holds the properties of the parent
     * daemon. Can be inheritted, or overridden by using
     * the $properties parameter of the constructor
     *
     * @var array
     */
    protected $daemonProperties = array();
    
    /**
     * Cache that holds values of some functions 
     * for performance gain. Easier then doing 
     * if (!isset($this->XXX)) { $this->XXX = $this->XXX(); }
     * every time, in my opinion. 
     *
     * @var array
     */
    private $_intFunctionCache = array();
    
    /**
     * Hold OS information
     *
     * @var array
     */
    private $_osDetails = array();
        
    
    
    public function __construct(){
        // Get ancestors
        $ancs = $this->_getAncestors($this);
        foreach ($ancs as $i=>$anc) {
            $ancs[$i] = $this->_getShortHand($anc);
        }
        
        // Set OS Details
        $this->_osDetails["shorthand"] = $this->_getShortHand(get_class($this));
        $this->_osDetails["ancestors"] = $ancs;
        
        // Up to date filesystem information
        clearstatcache();
    }
    
    
    
    public function isInstalled() 
    {
        
    }//end isInstalled
    
    public function getDetails()
    {
        return $this->_osDetails;
    }//end getDetails
    
    public function getAutoRunPath() 
    {
        
    }//end getAutoRunPath
    
    public function getAutoRunScript()
    {
        
    }//end getAutoRunScript()

    
    
    /**
     * Writes an: 'init.d' script on the filesystem
     *
     * @param bolean $overwrite May the existing init.d file be overwritten?
     * 
     * @return mixed boolean on failure, string on success
     * @see initDLocation()
     * @see initDForge()
     */
    public function writeAutoRun($overwrite = false)
    {
        
        // Collect init.d path
        $initd_location = $this->initDLocation();
        if (!$initd_location) {
            // Explaining errors should have been generated by 
            // System_Daemon_OS::initDLocation() 
            // already
            return false;
        }
        
        // Collect init.d body
        $initd_body = $this->initDForge();
        if (!$initd_body) {
            // Explaining errors should have been generated by osInitDForge() 
            // already
            return false;
        }
        
        // As many safety checks as possible
        if (!$overwrite && file_exists(($initd_location))) {
            $this->errors[] = "init.d script already exists";
            return false;
        } 
        if (!is_dir($dir = dirname($initd_location))) {
            $this->errors[] =  "init.d directory: '".
                $dir."' does not ".
                "exist. Can this be a correct path?";
            return false;
        }
        if (!is_writable($dir = dirname($initd_location))) {
            $this->errors[] =  "init.d directory: '".
                $dir."' cannot be ".
                "written to. Check the permissions";
            return false;
        }
        
        if (!file_put_contents($initd_location, $initd_body)) {
            $this->errors[] =  "init.d file: '".
                $initd_location."' cannot be ".
                "written to. Check the permissions";
            return false;
        }
        
        if (!chmod($initd_location, 0777)) {
            $this->errors[] =  "init.d file: '".
                $initd_location."' cannot be ".
                "chmodded. Check the permissions";
            return false;
        } 
        
        return $initd_location;
    }//end writeAutoRun() 

    

    /**
     * Sets daemon specific properties
     *  
     * @param array $properties Contains the daemon properties
     * 
     * @return array
     */       
    private function _testAutoRunProperties($properties = false) 
    {
        if (!is_array($properties) || !count($properties)) {
            $this->errors[] = "No properties to ".
                "forge init.d script";
            return false; 
        }
                
        // Tests
        $required_props = array("appName", "appExecutable", 
            "appDescription", "appDir", 
            "authorName", "authorEmail");
        
        // Check if all required properties are available
        $success = true;
        foreach ($required_props as $required_prop) {
            if (!isset($properties[$required_prop])) {
                $this->errors[] = "Cannot forge an ".
                    "init.d script without a valid ".
                    "daemon property: ".$required_prop;
                $success        = false;
                continue;
            }            
        }
        
        // Check path
        $daemon_filepath = $properties["appDir"]."/".$properties["appExecutable"];
        if (!file_exists($daemon_filepath)) {
            $this->errors[] = "unable to forge startup script for non existing ".
                "daemon_filepath: ".$daemon_filepath.", try setting a valid ".
                "appDir or appExecutable";
            $success = false;
        }
        
        // Daemon file needs to be executable 
        if (!is_executable($daemon_filepath)) {
            $this->errors[] = "unable to forge startup script. ".
                "daemon_filepath: ".$daemon_filepath.", needs to be executable ".
                "first";
            $success = false;
        }
        
        return $success;
        
    } // end setProperties    
    
    private function _getShortHand($class) {
        $parts = explode("_", $class);
        return end($parts);
    }
    
    /**
     * Get an array of parent classes
     *
     * @param string $class
     * 
     * @return array
     */
    private function _getAncestors($class) {
        $classes     = array();
        while($class = get_parent_class($class)) { 
            $classes[] = $class; 
        }
        return $classes;
    }  
    
}//end class
?>