<?php
abstract class Command {
        
        const OPTIONAL = 0;
        const REQUIRED = 1;
        const VALUE_NONE = 2;
        const VALUE_IS_ARRAY = 8;
        
        private static $stty = null;
                
        private $arguments = array();
        
        private $options = array();
        
        protected $name;
        
        protected $quiet;
        
        // Set up shell colors
        private $foreground_colors = array(
                'black'                        => '0;30',
                'dark_gray'                => '1;30',
                'blue'                        => '0;34',
                'light_blue'        => '1;34',
                'green'                        => '0;32',
                'cyan'                        => '0;36',
                'light_cyan'        => '1;36',
                'red'                        => '0;31',
                'light_red'                => '1;31',
                'purple'                => '0;35',
                'light_purple'        => '1;35',
                'brown'                        => '0;33',
                'yellow'                => '1;33',
                'light_gray'        => '0;37',
                'white'                        => '1;37',
        );
        
        // Set up shell colors
        private $background_colors = array(
                'black'                        => '40',
                'red'                        => '41',
                'green'                        => '42',
                'yellow'                => '43',
                'blue'                        => '44',
                'magenta'                => '45',
                'cyan'                        => '46',
                'light_gray'        => '47',
        );
        
        public function __construct($name, $arguments, $options) {
                $this->name = $name;

                if (isset($options['help']) || isset($options['h']))
                {
                    $this->showUsage();
                    $this->bail();
                }
        
                $this->buildArguments($arguments);
                $this->buildOptions($options);
        
                if($this->getOption('quiet')){
                    $this->quiet = true;
                }
        }
        
        private function buildArguments($arguments)
        {

            foreach ($this->getArguments() as $i => $argument) {

                if (!isset($arguments[$i]) && ($argument[1] === self::REQUIRED))
                {
                    $this->showUsage();
                    $this->bail();
                }

                if (isset($arguments[$i]))
                {
                    $this->arguments[$argument[0]] = $arguments[$i];
                }elseif(isset($argument[3]))
                {
                    $this->arguments[$argument[0]] = $argument[3];
                }else
                {
                    $this->arguments[$argument[0]] = null;
                }
            }
        }
        private function getMergedOptions()
    {
        return array_merge(array(
            array('help', 'h', self::VALUE_NONE, 'Display help'),
            array('quiet', 'q', self::VALUE_NONE, 'Suppress all output'),
            array('verbose', 'v', self::OPTIONAL, 'Set the verbosity level'),
                ), $this->getOptions());
    }

        private function buildOptions($options)
    {

        foreach ($this->getMergedOptions() as $option) {

            $set = isset($options[$option[0]]) || isset($options[$option[1]]);

            if ($set)
            {
                $value = isset($options[$option[0]]) ? $options[$option[0]] : $options[$option[1]];
            }

            $array = self::VALUE_IS_ARRAY | self::REQUIRED;

            if ($set && (($option[2] & $array) === $array))
            {
                $this->showUsage();
                $this->bail();
            }

            if ($set && !$value && (($option[2] & self::REQUIRED) === self::REQUIRED))
            {
                $this->fatal('Value for option "--' . $option[0] . ' (-' . $option[1] . ')" is required');
            }

            if ($set && !is_bool($value) && strlen($value) > 0 && (($option[2] & self::VALUE_NONE) === self::VALUE_NONE))
            {
                $this->fatal('Cannot set a value for option "--' . $option[0] . ' (-' . $option[1] . ')"');
            }

            if ($set)
            {
                $this->options[$option[0]] = (($option[2] & self::VALUE_NONE) === self::VALUE_NONE) ? true : $value;
            }elseif(isset($option[4]))
            {
                $this->options[$option[0]] = $option[4];
            }else
            {
                $this->options[$option[0]] = null;
            }
        }
    }
        
        // Returns colored string
        private function getColoredString($string, $foreground_color = null, $background_color = null) {
                $colored_string = "";

                // Check if given foreground color found
                if (isset($this->foreground_colors[$foreground_color])) {
                        $colored_string .= "\033[" . $this->foreground_colors[$foreground_color] . "m";
                }
                // Check if given background color found
                if (isset($this->background_colors[$background_color])) {
                        $colored_string .= "\033[" . $this->background_colors[$background_color] . "m";
                }

                // Add string and end coloring
                $colored_string .=  $string . "\033[0m";

                return $colored_string;
        }
        
        private function hasSttyAvailable()
    {
        if (null !== self::$stty) {
            return self::$stty;
        }

        exec('stty 2>&1', $output, $exitcode);

        return self::$stty = $exitcode === 0;
    }
        
        private function showUsage()
            {
        
                $cmd = '';
        
                foreach ($this->getArguments() as $argument) {
        
                    if (($argument[1] & self::REQUIRED)  === self::REQUIRED)
                    {
                        $cmd .= ' ' . $argument[0];
                    } elseif (($argument[1] & self::OPTIONAL) === self::OPTIONAL)
                    {
                        $cmd .= ' [' . $argument[0] . ']';
                    }
                }
        
                $this->line('Usage: ' . $this->name . ' ' . $cmd);
        
                $required = '';
        
                foreach ($this->getMergedOptions() as $argument) {
        
                    $strlen = strlen($argument[0]) + 3;
                    
                    $tabSize = max(3 - floor($strlen / 8),1);
        
                    $required .= "\n\t --" . $argument[0] . str_repeat("\t",$tabSize);
        
                    if($argument[1]) {
                            
                            $required .= '(-' . $argument[1];
                            
                            if (($argument[2] & self::REQUIRED) === self::REQUIRED)
                            {
                                $required .= '=""';
                            } elseif (($argument[2] & self::OPTIONAL) === self::OPTIONAL)
                            {
                                $required .= '[=""]';
                            }
                
                            $required .= ")\t" . $argument[3];
                    }
                    else {
                            $required .= "         \t";
                    }
                    
                    $required .= $argument[3];
                }
        
                $this->line($required);
            }
        
        protected function ask($question){
                
                $this->line($question);

                return $this->readInput();
        }
        
        protected function secret($question){
                
                if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            $exe = __DIR__.'/../Resources/bin/hiddeninput.exe';

            // handle code running from a phar
            if ('phar:' === substr(__FILE__, 0, 5)) {
                $tmpExe = sys_get_temp_dir().'/hiddeninput.exe';
                copy($exe, $tmpExe);
                $exe = $tmpExe;
            }

            $this->line($question);
            $value = rtrim(shell_exec($exe));
            $this->line('');

            if (isset($tmpExe)) {
                unlink($tmpExe);
            }

            return $value;
        }

        if ($this->hasSttyAvailable()) {
            $this->line($question);

            $sttyMode = shell_exec('stty -g');

            shell_exec('stty -echo');
            $value = $this->readInput();
            shell_exec(sprintf('stty %s', $sttyMode));

            if (false === $value) {
                throw new \RuntimeException('Aborted');
            }

            $this->line('');

            return $value;
        }

        if (false !== $shell = $this->getShell()) {
            $this->line($question);
            $readCmd = $shell === 'csh' ? 'set mypassword = $<' : 'read -r mypassword';
            $command = sprintf("/usr/bin/env %s -c 'stty -echo; %s; stty echo; echo \$mypassword'", $shell, $readCmd);
            $value = rtrim(shell_exec($command));
            $this->line('');

            return $value;
        }

        return $this->ask($question);

        }
        
        protected function confirm($question){
                
                $this->line($question . " Y/n");

                $line = strtolower($this->readInput());
                
                return ($line === 'y' || $line === 'yes');
        }

        private function readInput(){
                return trim(fgets(STDIN));
        }

        protected function line($output){
                if(!$this->quiet)
                {
                        echo $output . "\n";
                }
        }
        
        protected function info($output){
                $this->line($this->getColoredString($output,'blue'));
        }
        
        protected function success($output){
                $this->line($this->getColoredString($output,'green'));
        }
        
        protected function error($output){
                $this->line($this->getColoredString($output,'white','red'));
        }
        
        protected function fatal($output,$exitcode = 1){
                $this->error($output);
                $this->bail($exitcode);
        }
        
        protected function bail($exitcode = 1)
        {
             exit($exitcode);   
        }
        
        protected function getArgument($name,$default = null){
                return isset($this->arguments[$name]) ? $this->arguments[$name] : $default;
        }
        
        protected function getOption($name,$default = false){
                return isset($this->options[$name]) ? $this->options[$name] : $default;
        }

        /**
         * Get the console command arguments.
         *
         * @return array
         */
        protected function getOptions()
        {
                return array();
        }
        
        /**
         * Get the console command options.
         *
         * @return array
         */
        protected function getArguments()
        {
                return array();
        }
        
        public static function createFromCliArgs(){
                
                // Build the arguments list
                $arguments = array();
                $options = array();
                                
                $i = 0;
                
                foreach(array_slice($_SERVER['argv'],1) as $arg){
                        
                        if($arg[0] === '-'){
                                // it's an option
                                $parts = explode('=',ltrim($arg,'-'));
                                
                                $options[$parts[0]] = isset($parts[1]) ? $parts[1] : true;
                                
                        }else{
                                $arguments[$i++] = $arg;
                        }
                }

                return new static($_SERVER['argv'][0], $arguments, $options);

        }
        
        abstract public function fire();
        
}
