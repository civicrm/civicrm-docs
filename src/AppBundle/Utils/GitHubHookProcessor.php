<?php

namespace AppBundle\Utils;

class GitHubHookProcessor{
    
    protected $messages = array(); 

    protected $recipients = array(); 
    
    public $published = false;
    
    public function __construct($publisher, $bookLoader){
        $this->publisher = $publisher;
        $this->books = $bookLoader->find();
    }
    
    function process($event, $payload){
        
        //The getDetailsFrom functions work out what branch and repo we are talking about, and the also work out what emails we should send.
        switch ($event) {
            case 'pull_request':
            $this->getDetailsFromPullRequest($payload);
            break;
            case 'push':
            $this->getDetailsFromPush($payload);
            break;
        }
        foreach ($this->books as $bookName => $bookConfig) {
            foreach ($bookConfig['langs'] as $bookLang => $bookLangConfig) {
                if($bookLangConfig['repo'] == $this->repo){
                    $this->book = $bookName;
                    $this->lang = $bookLang;
                    $config = $bookLangConfig;
                    break 2;
                }
            }
        }

        // If the book (in a specific language) wants additional people to be notified on each publication, they can be added in the book yml definition and will get added here
        if(isset($config['notify'])){
            foreach($config['notify'] as $recipient){
                $this->recipients[]=$recipient;
            }
        }

        $this->published = $this->publisher->publish($this->book, $this->lang, $this->branch);
        $this->messages = $this->publisher->getMessages();
        $this->subject = "Published '{$this->branch}' branch of '{$this->book}' in '{$this->lang}'";
        $this->recipients = array_unique($this->recipients);
    }
    
    function getDetailsFromPullRequest($payload){
    
        //Only continue if this pull request is closed and merged
        if($payload->action != 'closed' OR !$payload->pull_request->merged){
            return;
        }
        
        //Work out what book, language and branch to publish
        $this->branch = $payload->pull_request->base->ref;
        $this->repo = $payload->repository->html_url;
        
        //Get emails of people that should be notified
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $payload->pull_request->commits_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json', 'User-Agent: civicrm-docs')); // Assuming you're requesting JSON
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $this->commits = json_decode(curl_exec($ch));

        foreach($this->commits as $commit){
            $this->recipients[]=$commit->commit->author->email;
            $this->recipients[]=$commit->commit->committer->email;
        }

    }

    function getDetailsFromPush($payload){
        $this->branch = preg_replace("/.*\/(.*)/", "$1", $payload->ref);
        $this->repo = $payload->repository->html_url;        
        
        foreach($payload->commits as $commit){
            $this->recipients[]=$commit->author->email;
            $this->recipients[]=$commit->committer->email;
        }
    }
    
    public function getMessages()
    {
        return $this->messages;
    }

    public function getRecipients()
    {
        return $this->recipients;
    }

    public function getSubject()
    {
        return $this->subject;
    }


    
}
