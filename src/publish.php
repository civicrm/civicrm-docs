<?php
require_once '../vendor/autoload.php';
require_once '../conf/config.php';

use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Dumper;
use Gitonomy\Git as Gitonomy;
use Symfony\Component\Process\Process;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\ErrorHandler;
require_once 'ScreenHandler.php';
require_once 'HtmlLineFormatter.php';

//Start up a logger
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CiviCRM documentation publisher</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
</head>
<body>
    
</body>
</html>
<div class="container">';

echo '<h1>CiviCRM documentation publisher</h1>';
echo '<ul>';
echo '<li><a href="https://github.com/civicrm/civicrm-docs">Documentation</a></li>';
echo '<li><a href="/log">logs</a></li>';
echo '</ul>';
echo '<h2>Results</h2>';

$publisher = new Publisher($config);
$result = $publisher->publish();
if($result == 0){
    $publisher->bookLog->addCritical("Book was not published (see errors above).");

}

class publisher
{
    public $docsLog;
    public $bookLog;
    public $logHandler;
    public $screenHandler;
    public $formatter;

    public function __construct($config)
    {
        $this->config = $config;

        $this->formatter = new HtmlLineFormatter();

        $this->docsLog = new Logger('Docs');

        $logHandler = new StreamHandler("{$this->config['root']}/log/docs.log.html", Logger::DEBUG);
        $logHandler->setFormatter($this->formatter);
        $this->docsLog->pushHandler($logHandler);

        $this->screenHandler = new ScreenHandler(Logger::INFO);
        $this->screenHandler->setFormatter($this->formatter);
        $this->docsLog->pushHandler($this->screenHandler);
        ErrorHandler::register($this->docsLog);
    }

    public function publish()
    {
        // These details should be pulled from the URL, or JSON or similar
        $validParams = array(
            'book',
            'branch',
            'lang',
        );

        //Do some basic validation here
        foreach ($validParams as $param) {
            if (!isset($_GET[$param])) {
                $this->docsLog->addError("'$param' is a required parameter. Exiting...");

                return 0;
            }
            if (!preg_match('/^[a-z0-9\.]*$/', $_GET[$param])) {
                $this->docsLog->addError("$param ({$_GET[$param]}) is not alphanumeric. Exiting...");

                return 0;
            }
            $$param = $_GET[$param];
        }

        //Validate that the book is defined
        if (!file_exists("{$this->config['root']}/conf/books/$book.yml")) {
            $this->docsLog->addError("Could not find config file '{$book}.yml'. Exiting...");

            return 0;
        }
        $this->docsLog->addInfo("Found config file '{$book}.yml'. Will attempt to publish '{$branch}' branch of '{$book}' in language: '{$lang}'");

        // Load the configuration
        $yaml = new Parser();
        $bookConf = $yaml->parse(file_get_contents("{$this->config['root']}/conf/books/$book.yml"));

        if (!file_exists("{$this->config['root']}/repos/{$book}/{$lang}")) {
            $repo = Gitonomy\Admin::cloneTo("{$this->config['root']}/repos/{$book}/{$lang}", $bookConf[$lang]['repo'], false);
        } else {
            $repo = new Gitonomy\Repository("{$this->config['root']}/repos/{$book}/{$lang}");
        }

        $this->bookLog = new Logger('Docs');

        $logHandler = new StreamHandler("{$this->config['root']}/log/{$book}.log.html", Logger::INFO);
        $logHandler->setFormatter($this->formatter);
        $this->bookLog->pushHandler($logHandler);

        $this->bookLog->pushHandler($this->screenHandler);

        $repo->setLogger($this->bookLog);

        $this->bookLog->addNotice("Publishing started for '$book' documentation.");
        // Ensure that we are on the right branch to do the publishing, and that the branch is up to date

        //If this branch exist locally, check it out. Else, ensure that it exists in origin, and if so check it out (also serves to validate URL parameter)
        $references = $repo->getReferences();

        if ($references->hasBranch($branch)) {
            $repo->run('checkout', array($branch));
        } else {
            $this->bookLog->addInfo("Can't find local tracking branch for $branch. Will attempt to track remote branch");
            if ($references->hasRemoteBranch("origin/$branch")) {
                $repo->run('checkout', array($branch));
                $this->bookLog->addInfo("Remote branch $branch found. Tracking remote branch.");
            } else {
                $this->bookLog->addError("Remote branch $branch not found. Exiting...");

                return 0;
            }
        }

        //Update the branch by pulling in any changes from origin
        $repo->run('pull');

        //Override some settings from the yml before publishing
        $mkdocsConf = $yaml->parse(file_get_contents("{$this->config['root']}/repos/$book/{$lang}/mkdocs.yml"));

        //@TODO create a CiviCRM theme so that we can uncomment this line
        //@TODO just use one logger and log to different channels as set these up as going to different files
        //$mkdocsConf['theme_dir']="{$this->config['root']}/themes/civicrm";
        //@TODO script to update symlinks so that latest and stable point to the right places

        $dumper = new Dumper();
        file_put_contents("{$this->config['root']}/conf/builds/{$book}.{$lang}.{$branch}.yml", $dumper->dump($mkdocsConf, 2));

        $command = "mkdocs build -c -f {$this->config['root']}/conf/builds/{$book}.{$lang}.{$branch}.yml -d {$this->config['root']}/www/{$book}/{$lang}/{$branch}";
        $dir = "{$this->config['root']}/repos/{$book}/{$lang}";
        $mkdocs =
        new Process($command, $dir);
        $mkdocsErrors = false;
        $mkdocs->run();
        //echo nl2br($mkdocs->getOutput()); (don't think anything gets outputed by this command)
        $mkdocsLogMessages = explode("\n", $mkdocs->getErrorOutput());
        foreach ($mkdocsLogMessages as $mkdocsLogMessage) {
            
            //mkdocs does a Traceback which is fairly uninteresting and we don't want to display it
            if(substr( $mkdocsLogMessage, 0, 9 ) === "Traceback"){
                break;
            }elseif($mkdocsLogMessage){
                list($label, $message) = explode('-', $mkdocsLogMessage, 2);
                $label = strtoupper(trim($label));
                $level = constant("Monolog\Logger::$label");
                if($label == 'ERROR'){
                    $mkdocsErrors = true;
                }
                $message = 'mkdocs build: '.trim($message);
                $this->bookLog->addRecord($level, $message);
            }
        }
        if($mkdocsErrors){
            $this->bookLog->addCritical("Book published with errors (see above for details) at <a href='{$this->config['host']}/{$book}/{$lang}/{$branch}'>{$this->config['host']}/{$book}/{$lang}/{$branch}</a></strong>.");
            return 1;
        }else{
            //not sure how to make this
            $this->bookLog->addNotice("Book published successfully at <a href='{$this->config['host']}/{$book}/{$lang}/{$branch}'>{$this->config['host']}/{$book}/{$lang}/{$branch}</a></strong>.");
            return 1;
        }
    }
}
echo "</div>"
?> 
