<?php
/**
* Morgen (stands for MObile Resource GENerator)
* Generating media resources for all registered mobile device "dencities"
* from "original" files (only images for now supported)
* @version 0.1
* PHP required: 5.3+
* If you need 5.2-, delete namespace and use IconGenerator instead of \Morgen\IconGenerator
* @author Alexander Selifonov < alex [at] selifan {dot} ru >
* @link https://github.com/selifan/moregen
* @license MIT
*/
namespace Morgen;
class IconGenerator {

	const DEFAULT_MASKS = '*.png,*.jpg,*img,*.svg';

    private $_verbose = 0; // verbose log level
    private $_flags = array();
    private $_wrkdevices = array();

    protected $project = array();

	// default configuration values: 'devices' - devices for all "registered" tablet/smartphone sizes
	protected $_cfg = array(
	   'jpgquality' => 80
	  ,'pngquality' => 0
	  ,'svgexportCmd' => 'inkscape -z {from} -e {to}'
	  ,'forced' => false
	);

	private $_apptypes = array(
/*
		'android' => array(
          'folderProjects' => '/android/projects/'
          ,'projectFolderTpl' => '{projectname}/resources/'
		  ,'devices' => array(
			 'xxhdpi'=> array('width'=>144, 'folder'=>'drawable-mdpi')
			,'xhdpi' => array('width'=> 96,'folder'=>'drawable-xhdpi')
			,'hdpi'  => array('width'=> 72,'folder'=>'drawable-hdpi')
			,'mdpi'  => array('width'=> 48,'folder'=>'drawable-mdpi')
		  )

		)
		,'ios' => array(
          'folderProjects' => '/ios/myprojects/'
          ,'projectFolderTpl' => '{projectname}/Resources/'
		  ,'devices' => array(
			 'iphone' => array('width'=> 57, 'namePostfix'=>'')
			,'iphone5'=> array('width'=> 114, 'namePostfix'=>'@2x')
			,'ipad'   => array('width'=> 72, 'namePostfix'=>'@72')
			,'retina' => array('width'=>144, 'namePostfix'=>'@144')
		  )
		)
*/
	);

	private $_destfolder = '';

	public function __construct($cfgFile='', $projectFile=false) {
        $this->loadConfig($cfgFile);
        if ($projectFile) $this->loadProjectDef($projectFile);
	}

	/**
	* Reading base configurations from XML file
	*
	* @param mixed $fname user XML config file name or FALSE to use "default" morgen.cfg.xml
	*/
	public function loadConfig($fname = false) {

		$cfgFile = ($fname) ? $fname : (__DIR__ . DIRECTORY_SEPARATOR . 'morgen.cfg.xml');
#		echo "load from $cfgFile : " . (is_file($cfgFile) ? 'exist':'no file!');
		if (!is_file($cfgFile)) return;
        $xml = @simplexml_load_file($cfgFile);

		if (isset($xml->svgexportCmd))
			$this->_cfg['svgexportCmd'] = (string) $xml->svgexportCmd;

		if (isset($xml->commonParams)) {
            if (isset($xml->commonParams['jpgQuality']))
				$this->_cfg['jpgquality'] = (int) $xml->commonParams['jpgQuality'];
            if (isset($xml->commonParams['pngQuality']))
				$this->_cfg['pngquality'] = (int) $xml->commonParams['pngQuality'];
            if (isset($xml->commonParams['verbose']))
				$this->_verbose = (int) $xml->commonParams['verbose'];
		}

		if (isset($xml->configurations)) foreach ($xml->configurations->children() as $cfgItem)
		{
			$id = isset($cfgItem['id']) ? (string)$cfgItem['id'] : '';
			if (!$id) continue;
			$cfgarr = array(
               'folderProjects'   => (isset($cfgItem->folderProjects) ? (string)$cfgItem->folderProjects : '')
              ,'projectFolderTpl' => (isset($cfgItem->projectFolderTpl) ? (string)$cfgItem->projectFolderTpl : '')
			);

			if (isset($cfgItem->devices)) {

				$dlist = array();
				foreach ($cfgItem->devices->children() as $item=>$dev) {
					$devname = isset($dev['name']) ? (string) $dev['name'] : '';
					if (!$devname) continue;
					$dlist[$devname] = array(
               			'folder'  => (isset($dev['folder']) ? (string)$dev['folder'] : '')
               			,'width'  => (isset($dev['width'] ) ? (int)$dev['width'] : '')
               			,'namePostfix' => (isset($dev['namePostfix'] ) ? (string)$dev['namePostfix'] : '')
					);
				}
				$cfgarr['devices'] = $dlist;
			}

			$this->_apptypes[$id] = $cfgarr;
		}
		else {
            $this->writeLog("Wrong Config xml (configurations section must exist)");
			exit;
		}

	}

    /**
    * Loads developer project definition from passed XML file
    *  or XML string
    * @param mixed $xmlName existing XML file name
    */
	public function loadProjectDef($xmlName) {

		$xml = false;
		if (is_file($xmlName))
			$xml = @simplexml_load_file($xmlName);
		elseif(substr($xmlName,0,5) === '<?xml')
			$xml = @simplexml_load_string($xmlName);

		if (!$xml){
			$this->writeLog("Wrong project xml file name/non XML string. Exiting");
			exit;
		}
        if (!isset($xml->project)) {
			$this->writeLog("Xml should contain 'project' tag. Exiting");
			exit;
		}
#		print_r($xml->project);
		$this->project = array();
		$this->project['name'] = isset($xml->project['name']) ? (string)$xml->project['name'] : 'noname';
		$this->project['srcpath'] = isset($xml->project['sourceFolder']) ? (string)$xml->project['sourceFolder'] : './';
		$this->project['branches'] = array();

		foreach($xml->project->branch as $br) {

            $branch = array(
				'type' => (isset($br['type']) ? (string)$br['type'] : 'android')
				,'destpath' => (isset($br['destinationFolder']) ? (string)$br['destinationFolder'] : 'out/')
			);
			if (!isset($this->_apptypes[$branch['type']])) {
				$this->writeLog("Unknown application type: " . $branch['type'] . ' - skipped!');
				continue;
			}
			if (isset($br->images)) {
				$imgs = array();
				foreach($br->images->children() as $imgitem) {
                    $imgs[] = array(
						'mask' => (isset($imgitem['mask']) ? (string)$imgitem['mask'] : '')
						,'devices' => (isset($imgitem['devices']) ? explode(',',(string)$imgitem['devices']) : false)
					);
				}
				$branch['images'] = $imgs;
			}
			$this->project['branches'][] = $branch;
		}
		return true;
#		print_r($this->project); exit;
	}

	public function setSvgConvertor($cmdTemplate) {
		$this->_cfg['svgexportCmd'] = $cmdTemplate;
	}
	/**
	* Creates icon set from image file, for all registered devices
	*
	* @param mixed $srcfolder
	* @param mixed $destfolder
	* @param mixed $fname
	*/
	public function createIconsFromImages($params) {
		if (is_string($params)) {
			$prj = $params;
			$this->_cfg['forced'] = false;
		}
		elseif(is_array($params)) {
			$prj = isset($params['project']) ? $params['project'] : '';
			$this->_cfg['forced'] = isset($params['forced']) ? $params['forced'] : false;
		}
#	    if (!$fmask) $fmask = '*.jpg,*.png,*.gif,*.svg';
#	    echo "configuration: "; print_r($this->_apptypes);
        if ($prj) $this->loadProjectDef($prj);
#		echo 'project:<pre>'.print_r($this->project,1) . '</pre>';
        if (!isset($this->project['branches'])) {
            $this->writeLog("No branches set. Empty project. Exiting");
        	exit;
		}

        $this->writeLog("Handling project: " . $this->project['name']);

	    $this->_flags = array();

	    foreach($this->project['branches'] as $branch) {

            $ptype = $branch['type'];
            if (!isset($this->_apptypes[$ptype])) {
                $this->writeLog("$ptype: undefined application type");
            	continue;
			}
            $this->_destfolder = $branch['destpath'];

            if (empty($this->_destfolder) && !empty($this->_apptypes[$ptype]['projectFolderTpl'])) {
            	$this->_destfolder = self::endWithSlash($this->_apptypes[$ptype]['folderProjects'])
            	  . str_replace('{projectname}', $this->project['name'], $this->_apptypes[$ptype]['projectFolderTpl']);
			}

	    	$img = ( isset($branch['images']) ? $branch['images'] : array(array('mask'=>'', 'devices'=>false) ));

	    	foreach($img as $imitem) {

            	$this->_wrkdevices = !empty($imitem['devices']) ? $imitem['devices'] : array_keys($this->_apptypes[$ptype]['devices']);

                $fmask = empty($imitem['mask']) ? self::DEFAULT_MASKS : $imitem['mask'];
				$srcfullname = self::endWithSlash($this->project['srcpath']) . '{'.$fmask. '}';
				$flist = glob($srcfullname,GLOB_BRACE);
		        if ($this->_verbose > 1) $this->writeLog('search full mask: ' . $srcfullname);

				if (is_array($flist) && count($flist)) {
					foreach($flist as $srcname) {
						$this->_oneIconSet($ptype, $srcname, $this->_wrkdevices);
					}
				}

			}
		}
    }

    private function _oneIconSet($apptype, $src, $devices='') {
        $fext = strtolower( substr($src, strrpos($src, '.')));
        $justname = basename($src);
        $img = false;

        if (!$devices || !is_array($devices))
        	$devices = array_keys($this->_apptypes[$apptype]['devices']);

        $tmpname = false;
        switch( $fext) {
        	case '.jpg': case '.jpeg':
				$img = imagecreatefromjpeg($src);
#				echo "$img - imagecreatefromjpeg\r\n";
				break;
        	case '.png':
				$img = imagecreatefrompng($src);
#				echo "$img - imagecreatefrompng\r\n";
				break;

			case '.svg': // export svg to PNG and set current output format from _src['']
				if (!is_dir($this->_destfolder)) {
					$ok = @mkdir($this->_destfolder, 0777, true);
				}
                $tmpname = self::endWithSlash($this->_destfolder) . "_tmp_from_svg.png";
				$cmd = str_replace(
				   array('{from}','{to}')
				  ,array($src, $tmpname)
				  ,$this->_cfg['svgexportCmd']
				);
				$this->writeLog("executing command: $cmd ...");
				exec($cmd, $outStrings, $svgresult);
				if ($this->_verbose) {
					$this->writeLog("converting to $tmpname result: $svgresult");
					$this->writeLog($outStrings);
				}
				if (/*$ok == 0 && */ is_file($tmpname)) {
					$img = imagecreatefrompng($tmpname);
#					echo "$img - imagecreatefrompng(svg:$tmpname)\r\n";
					unlink($tmpname);
				}
				break;

        	case '.gif':
				$img = imagecreatefromgif($src);
				break;
		}

		if (!$img) {
            $this->writeLog("$src - undefined/unsupported image type, skipped");
			return;
		}

		foreach ($devices as $devid) {
            if (!isset($this->_apptypes[$apptype]['devices'][$devid])) {
                $this->writeLog("Undefined device id '$devid' in '$apptype' app type");
            	continue;
			}
#            echo ('=== $this->_apptypes[$apptype]:'); print_r($this->_apptypes[$apptype]);

            $subpath = $this->_apptypes[$apptype]['devices'][$devid]['folder'];
            $sizeX   = $this->_apptypes[$apptype]['devices'][$devid]['width'];
            $postFix = $this->_apptypes[$apptype]['devices'][$devid]['namePostfix'];
#            $this->writeLog("type=[$apptype], device=$devid / path:[$subpath], size:[$size], postfix:[$postFix]");


			$destfold = $this->_destfolder;
			if ($subpath) $destfold = self::endWithSlash($this->_destfolder) . $subpath;
			if (!is_dir($destfold)) {
				$ok = @mkdir($destfold, 0777, true);
			}
			if (is_dir($destfold)) {
				$old_x = imageSX($img);
				$old_y = imageSY($img);
				$sizeY = round($sizeX * $old_y / $old_x);
                if ($postFix) {
                	$justname2 = substr($justname,0, strrpos($justname,'.'));
                	$ext = substr($justname, strrpos($justname,'.'));
                	$justname = $justname2 . $postFix . $ext;
				}

				$outputName = self::endWithSlash($destfold) . $justname;
				if ($fext === '.svg') $outputName = substr($outputName,0,-4) . '.png';
                $oldtm = filemtime($src);
                if (!$this->_cfg['forced']) {
                	if (is_file($outputName) && filemtime($outputName) >= $oldtm) {
                		if ($this->_verbose) $this->writeLog("$outputName already up to date (skipped)");
                		continue;
					}
				}

				switch($fext) {
					case '.jpg': case '.jpeg':
                        $newimg = imagecreatetruecolor($sizeX, $sizeY);
                        imagecopyresampled($newimg, $img,0,0,0,0,$sizeX,$sizeY,$old_x,$old_y);
						$result = @imagejpeg($newimg,$outputName,$this->_cfg['jpgquality']);
						break;

					case '.png': case '.svg':
                        $newimg = imagecreatetruecolor($sizeX, $sizeY);
						imagealphablending($newimg, false);
						imagesavealpha($newimg, true); // saving transparent pixels
                        imagecopyresampled($newimg, $img,0,0,0,0,$sizeX,$sizeY,$old_x,$old_y);
						$result = imagepng($newimg,$outputName, $this->_cfg['pngquality']);
						break;

					case '.gif':
                        $newimg = imagecreate($sizeX, $sizeY);
                        imagecopyresized($newimg, $img,0,0,0,0,$sizeX,$sizeY,$old_x,$old_y);
						$result = @imagegif($newimg,$outputName);
						break;
				}
				imagedestroy($newimg);

				if ($result)
					$this->writeLog("$outputName created OK");
				else
					$this->writeLog("$outputName creating ERROR!");
			}
			else { // creating folder failed
				if(!isset($this->_flags[$devid])) {
					$this->_flags[$devid] = 1;
					$this->writeLog("Creating folder failed: $devid");
				}
			}
		}
		imagedestroy($img);
	}

	# Just echoing log to stdout, but can be redefined
	public function writeLog($text) {
        if (is_array($text)) $text = implode("\r\n", $text);
		echo $text . "\r\n";
	}
	public static function endWithSlash($path) {
        if (!$path ) return '';
		$last = substr($path, -1);
		return $path . (($last === '/' || $last === '\\') ? '' : DIRECTORY_SEPARATOR);
	}
}
