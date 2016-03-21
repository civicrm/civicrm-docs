<?php

namespace AppBundle\Utils;

class GitHubHookProcessor{
    
    protected $messages = array(); 

    protected $recipients = array(); 
    
    public $published = false;
    
    public function __construct($publisher){
        $this->publisher = $publisher;
    }
    
    function process($event, $payload, $books){
        $this->books = $books;
        switch ($event) {
            case 'pull_request':
            $this->pullRequest($payload);
            break;
            case 'push':
            $this->push($payload);
            break;
        }
    }
    
    function PullRequest($payload){
        if($payload->action != 'closed' OR !$payload->pull_request->merged){
            return;
        }
        $branch = $payload->pull_request->base->ref;
        $repo = $payload->repository->html_url;
        foreach ($this->books as $bookName => $bookConfig) {
            foreach ($bookConfig['langs'] as $bookLang => $bookLangDetails) {
                if($bookLangDetails['repo'] == $repo){
                    $book = $bookName;
                    $lang = $bookLang;
                }
            }
        }
        
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $payload->pull_request->commits_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json', 'User-Agent: Awesome-Octocat-App')); // Assuming you're requesting JSON
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = json_decode(curl_exec($ch));
        
        //$commits = json_decode(file_get_contents('https://api.github.com/zen'));
        foreach($response as $commit){
            $recipients[]=$commit->commit->author->email;
            $recipients[]=$commit->commit->committer->email;
        }
        $recipients = array_unique($recipients);
        
        $this->publisher->publish($book, $lang, $branch);
        $this->details = array('book' => $book, 'lang' => $lang, 'branch' => $branch);
        $this->published = true;
        $this->subject = "Published '{$branch}' branch of '{$book}' in '{$lang}'";
        $this->messages = array_merge($this->messages, $this->publisher->getMessages());
        
    }

    function Push($event){
        //do something, then...
        $this->publisher->publish($book, $lang, $branch);
        array_merge($this->messages, $this->publisher->getMessages());

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

    public function getDetails()
    {
        return $this->details;
    }

    
}
