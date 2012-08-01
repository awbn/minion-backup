<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Backup mysql databases.
 * Requires mysqldump to be installed.
 *
 * --instance=default
 * Optional.  If no instance is specified, it will attempt to backup
 * all defined kohana instances.
 *
 * --gzip=true
 * Optional.  Compress backup.
 *
 * @author Barrett Amos <barrett.amos@gmail.com>
 */
class Minion_Task_Backup_Database extends Minion_Task {

	/**
	 * A set of config options that this task accepts
	 * @var array
	 */
	protected $_config = array(
		'instance',
		'gzip',
	);
	
	/**
	 * Execute the task
	 *
	 * @param array Config for the task
	 */
	public function execute(array $config)
	{
		$k_config = Kohana::$config->load('minion/backup');
		$db_config = Kohana::$config->load('database')->as_array();
		
		$instance = array_key_exists('instance', $config);
		
		if ($instance)
		{
			if (!array_key_exists($config['instance'],$db_config))
			{
				$message = "Database config '".$config['instance']."' was specified but does not exist as a Kohana database instance";
				
				Minion_CLI::write("Error: ".$message);
				Kohana::$log->add(Log::ERROR, "minion-backup: database: ".$message);
				return;
			}
			else
			{
				$instances = array($config['instance'] => $db_config[$config['instance']]);
			}
			
		}
		else
		{
			$instances = $db_config;
		}
		
		//Check for compression settings
		$gzip = array_key_exists('gzip', $config);
		$gzip = ($gzip) ? (bool) $config['gzip'] : $k_config->get('gzip');
		
		//Check for mysqldump
		$mysqldump = exec("which mysqldump");
		
		if (empty($mysqldump))
		{
			$message = "mysqldump not present on this system";
			Minion_CLI::write("Error: ".$message);
			Kohana::$log->add(Log::ERROR, "minion-backup: database: ".$message);
			return;
		}
		
		
		$directory 	= $k_config->get('directory');
		$name 		= $k_config->get('file_name');
		
		$status = array();
		
		foreach($instances as $instance => $db)
		{
			if (!array_key_exists('type', $db) OR $db['type'] != "mysql")
			{
				$status[$instance] = "Skipped. Not a MySQL database";
				continue;
			}
			
			$tokens = array(
				"%INSTANCE%" 	=> $instance,
				"%DATE%"		=> date("YmdHis"),
				"%TYPE%"		=> "db",
			);
			
			$file = $directory."/".strtr($name,$tokens).".sql";
			
			//mysqldump -h $DB1_HOST Ñport $DB1_PORT $DB1_NAME -u $DB1_USER -p $DB1_PASS | gzip > $DIR/bk_`date +\%Y\%m-\%d`.sql.gz
			$exec = "mysqldump";
			
			if (array_key_exists('hostname',$db['connection']))
			{				
				$port = strstr($db['connection']['hostname'], ':');
				$exec .= " -h ";
				$exec .= ($port) ? strstr($db['connection']['hostname'], ':',true) : $db['connection']['hostname']; 
			}

			if (array_key_exists('port',$db['connection']))
			{
				$port = $db['connection']['port'];
			}

			if(!empty($port) AND $port)
			{
				$port = str_replace(':','',$port);
				$exec .= " -port $port";
			}
			
			if (array_key_exists('database',$db['connection']))
			{
				$exec .= " ".$db['connection']['database'];
			}
			
			if (array_key_exists('username',$db['connection']))
			{
				$exec .= " -u ".$db['connection']['username'];
			}
			
			if (array_key_exists('password',$db['connection']))
			{
				$exec .= " -p". $db['connection']['password'];
			}
			
			
			if ($gzip)
			{
				$file .= ".gz";
				$exec .= " | gzip";
			}
			
			$exec .= " > ".$file;
			
			//Execute command
			$output = exec($exec);
			
			$status[$instance] = $file." ".$output;
			
			//Since this is a CLI command, log it
			Kohana::$log->add(Log::INFO, "minion-backup: database: '$instance' backed up to '$file'");
			
		}

		echo View::factory('minion/task/backup/database/backup')
			->set('instances',$status);

	}
}