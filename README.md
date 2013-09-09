php-cli
=======

A standalone interface loosely matching the new laravel 4.0 cli interface

```PHP

class ExecuteControllerCommand extends Command {


	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$controller = $this->getArgument('controller');
		
		$this->info($controller);
		
		$action = $this->getArgument('action');
		
		$this->error($action);
		
		$env = $this->getOption('env');
		
		$this->info($env);
		
		$verbose = $this->getOption('verbose');
		
		$this->line($verbose);
		
		if($this->confirm('Would you like to continue?')) {
		
			$password = $this->secret('Please enter a password:');
			
			$this->info($password);
			
			$this->success('Complete!');
		}

	}

	protected function getArguments()
	{
		return array(
			array('controller', self::REQUIRED, 'The name of the controller to execute'),
			array('action', self::OPTIONAL, 'The name of the action on the controller to execute'),
		);
	}

	protected function getOptions()
	{
		return array(
			array('env','e', self::OPTIONAL, 'The name of the environment'),
			array('verbose','v', self::VALUE_NONE, 'Debug mode'),
		);
	}

}

ExecuteControllerCommand::createFromCliArgs()->fire

```
