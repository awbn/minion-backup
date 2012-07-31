<?php defined('SYSPATH') or die('No direct script access.');

return array(
	"max_age"			=>	7,								//How many days to keep old backups
	"file_name" 	=>  "%TYPE%_%INSTANCE%_%DATE%",		//Backup file name pattern
	"directory"		=>	"backups",						//Backup directory name
	"gzip"			=>	TRUE,							//Compress backups?
);