<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Backup databases
 *
 * --age=7
 * Optional.  Trims backups older than this many days
 *
 * --dir=backups
 * Optional.  Which directory to trim
 *
 * @author Barrett Amos <barrett.amos@gmail.com>
 */
class Minion_Task_Backup_Trim extends Minion_Task {

	/**
	 * A set of config options that this task accepts
	 * @var array
	 */
	protected $_config = array(
		'age',
		'dir',
	);
	
	/**
	 * Execute the task
	 *
	 * @param array Config for the task
	 */
	public function execute(array $config)
	{
	
		$k_config = Kohana::$config->load('minion/backup');
		
		$dir = array_key_exists('dir', $config) ? $config['dir'] : $k_config->get('directory');
		$age = array_key_exists('age', $config) ? $config['age'] : $k_config->get('max_age');
		
		try
		{
			$num = $this->_trim($dir,$age);
			$message = "Trimmed $num files older than $age days from $dir";
			
			Minion_CLI::write($message);
			Kohana::$log->add(Log::INFO, "minion-backup: trim: ".$message);
		}
		catch(ErrorException $e)
		{
			Minion_CLI::write("Error: ".$e->getMessage());
			Kohana::$log->add(Log::ERROR, "minion-backup: trim: ".$e->getMessage());
		}
				

	}
	
	/**
	 * Trim old files
	 *
	 */
	protected function _trim($dir,$age)
	{
		//Convert days to seconds
		$age = $age * 24 * 60 * 60;
		
		if (!is_dir($dir))
			throw new ErrorException("'$dir' is not a directory");
		
		$dir .= "/";
		
		$count = 0;
		
		if ($handle = opendir($dir))
		{
		    while (false !== ($file = readdir($handle)))
		    {
		        if ( filemtime($dir.$file) <= time()-$age )
		        {
		           unlink($dir.$file);
		           $count++;
		        }
		    }
		    closedir($handle);
	    }
	
		return $count;	
	}
}