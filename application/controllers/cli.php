<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Cli extends MY_Controller
{
	function __construct()
	{
		parent::__construct();

		if (!$this->input->is_cli_request())
		{
			show_404();
		}
		
		cli_notice('notice', sprintf(__('Welcome to {{FOOL_NAME}} version %s'), FOOL_VERSION));
		cli_notice('notice', __('Write "php index.php cli help" to display all the available command line functions.'));
	}
	
	
	function _error($error)
	{
		switch($error)
		{
			case '_parameter_missing':
				cli_notice('error', __("Your request is missing parameters."));
				break;
			case '_parameter_board':
				cli_notice('error', __("Your request is missing parameters: add the board shortname."));
				break;
			case '_parameter_board_exist':
				cli_notice('error', __("The board you selected doesn't exist."));
				break;
			default:
				cli_notice('error', $error);
		}
	}
	
	/**
	 * Check if the command line works? 
	 */
	function ping()
	{
		cli_notice('notice', 'pong.');
	}
	
	/**
	 * Display the sections available 
	 */
	function help()
	{
		cli_notice('notice', '');
		cli_notice('notice', 'Available sections:');
		cli_notice('notice', 'php index.php cli ...');
		cli_notice('notice', '    database [help]      Display the database functions available');
		cli_notice('notice', '    asagi [help]         Display the functions related to the Asagi fetcher');
		cli_notice('notice', '    cron [help]          Display the long-running functions available');

		$this->plugins->run_hook('fu_cli_controller_after_help', array(), 'simple');

		cli_notice('notice', '    ping                 Pong');
	}
	
	/**
	 * Collection of tools that run heavy modifications of database
	 * 
	 * - create _search table
	 * - drop _search table 
	 * - convert to utf8mb4
	 */
	function database()
	{
		cli_notice('notice', __('Write "php index.php cli database help" for displaying the available commands for database manipulation.'));
		
		// get the segments
		$parameters = func_get_args();

		// redirect to help if there's no parameters
		if(!isset($parameters[0]))
		{
			$parameters[0] = 'help';
		}
		
		switch($parameters[0])
		{
			case 'help':
				cli_notice('notice', '');
				cli_notice('notice', 'Command list:');
				cli_notice('notice', 'php index.php cli database ...');
				cli_notice('notice', '    create_search <board_shortname>             Creates the _search table necessary if you don\'t have SphinxSearch');
				cli_notice('notice', '    drop_search <board_shortname>               Drops the _search table, good idea if you don\'t need it anymore after implementing SphinxSearch');
				cli_notice('notice', '    mysql_convert_utf8mb4 <board_shortname>     Converts the MySQL tables to support 4byte characters that otherwise get ignored.');
				cli_notice('notice', '    recreate_triggers <board_shortname>         Recreate triggers for the selected board.');
				break;
				
			// create the _search table for a specific board
			case 'create_search':
				if(!isset($parameters[1]))
					return $this->_error('_parameter_board');
				$board = $this->radix->get_by_shortname($parameters[1]);
				if(!$board)
					return $this->_error('_parameter_board_exist');
				$result = $this->radix->create_search($board);
				break;
			// drop the search table for a specific board
			case 'drop_search':
				if(!isset($parameters[1]))
					return $this->_error('_parameter_board');
				$board = $this->radix->get_by_shortname($parameters[1]);
				if(!$board)
					return $this->_error('_parameter_board_exist');
				$result = $this->radix->remove_search($board);
				break;
			// convert a specific board to utf8mb4
			case 'mysql_convert_utf8mb4':
				if(!isset($parameters[1]))
					return $this->_error('_parameter_board');
				$board = $this->radix->get_by_shortname($parameters[1]);
				if(!$board)
					return $this->_error('_parameter_board_exist');
				$result = $this->radix->mysql_change_charset($board);
				break;
			case 'recreate_triggers':
				if(!isset($parameters[1]))
					return $this->_error('_parameter_board');
				$board = $this->radix->get_by_shortname($parameters[1]);
				if(!$board)
					return $this->_error('_parameter_board_exist');
				$this->mysql_remove_triggers($board);
				$this->mysql_create_triggers($board);
				break;
				
		}
		
		// always give a response
		if (isset($result['error']))
		{
			cli_notice('error', $result['error']);
		}
		else if (isset($result['success']))
		{
			cli_notice('notice', $result['success']);
		}
	}


	function stats_cron()
	{
		$this->load->model('statistics_model', 'statistics');
		$done = FALSE;

		while (!$done)
		{
			$this->statistics->cron();
			sleep(30);
		}
	}


	function statistics($board = NULL)
	{
		$this->load->model('statistics_model', 'statistics');

		$this->statistics->cron($board);
	}

	
	
	function asagi_get_settings()
	{
		$this->load->model('asagi_model', 'asagi');
		
		echo json_encode($this->asagi->get_settings()).PHP_EOL;
	}

}
