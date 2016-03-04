<?php
require_once '../vendor/autoload.php';
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Dumper;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Gitonomy\Git as Gitonomy;
use Symfony\Component\Process\Process;

require_once '../conf/config.php';

//Start up a logger
$docsLog = new Logger('Docs');
$docsLog->pushHandler(new StreamHandler("$root/log/docs.log", Logger::INFO));

// These details should be pulled from the URL, or JSON or similar
$validParams = array(
    'book',
    'branch',
    'lang'
);

//Do some basic validation here
foreach($validParams as $param){
    if(!isset($_GET[$param])){
        $docsLog->addError("$param is a required parameter. Exiting...");
        exit;
    }
    if(!preg_match('/^[a-z0-9\.]*$/', $_GET[$param])){
        $docsLog->addError("$param ({$_GET[$param]}) is not alphanumeric. Exiting...");
        exit;
    }
    $$param = $_GET[$param];
}

//Validate that the book is defined
if(!file_exists("$root/conf/books/$book.yml")){
    $docsLog->addError("Could not find config file '$book.yml'. Exiting...");
    exit;
}

// Load the configuration
$yaml = new Parser;
$bookConf = $yaml->parse(file_get_contents("$root/conf/books/$book.yml"));
$docsLog->addInfo("Found config file '$book.yml'.");

if(!file_exists("$root/repos/{$book}")){
    $repo = Gitonomy\Admin::cloneTo("$root/repos/{$book}", $bookConf[$lang]['repo'], false);
}else{
    $repo = new Gitonomy\Repository("$root/repos/{$book}");
}
$bookLog = new Logger("Docs.$book");
$bookLog->pushHandler(new StreamHandler("$root/log/$book.book.log", Logger::INFO));
$repo->setLogger($bookLog);


// Ensure that we are on the right branch to do the publishing, and that the branch is up to date

//If this branch exist locally, check it out. Else, ensure that it exists in origin, and if so check it out (also serves to validate URL parameter)
$references = $repo->getReferences();

if($references->hasBranch($branch)){
    $repo->run('checkout', array($branch));
}else{
    $bookLog->addInfo("Can't find local tracking branch for $branch. Will attempt to track remote branch");
    if($references->hasRemoteBranch("origin/$branch")){
        $repo->run('checkout', array($branch));
        $bookLog->addError("Remote branch $branch found. Tracking remote branch.");
    }else{
        $bookLog->addError("Remote branch $branch not found. Exiting...");
        exit;
    }
}

//Update the branch by pulling in any changes from origin
$repo->run('pull');

//Override some settings from the yml before publishing
$mkdocsConf = $yaml->parse(file_get_contents("$root/repos/$book/mkdocs.yml"));

//@TODO create a CiviCRM theme so that we can uncomment this line
//$mkdocsConf['theme_dir']="$root/themes/civicrm";

$dumper = new Dumper;
file_put_contents("$root/conf/builds/{$book}.{$lang}.{$branch}.yml", $dumper->dump($mkdocsConf, 2));

$command = "mkdocs build -c -f $root/conf/builds/{$book}.{$lang}.{$branch}.yml -d $root/www/$book/{$lang}/{$branch}";
$dir = "$root/repos/{$book}";
$mkdocs = New Process($command, $dir);
$mkdocs->run();
echo nl2br($mkdocs->getOutput());
echo nl2br($mkdocs->getErrorOutput());
echo "<strong>Book published successfully at <a href='/{$book}/{$lang}/{$branch}'>/{$book}/{$lang}/{$branch}</a></strong>";
?>
