<?php

namespace AppBundle\Utils;

class GitHubHookProcessor {

  /**
   * @var array of strings for email addresses of people to notify
   */
  public $recipients = array();

  /**
   * @var string the URL for the repository
   */
  public $repo;

  /**
   * @var string the name of the branch to publish
   */
  public $branch;

  /**
   *
   */
  public function __construct() {

  }

  /**
   *
   * @param string $event  (e.g. 'pull_request', 'push')
   * @param mixed $payload An object given by json_decode()
   */
  public function process($event, $payload) {
    if (empty($payload)) {
      throw new \Exception("No payload data supplied");
    }
    if (empty($event)) {
      throw new \Exception("Unable to determine webhook event type from "
          . "request headers");
    }
    if ($event == 'pull_request') {
      $this->getDetailsFromPullRequest($payload);
    }
    elseif ($event == 'push') {
      $this->getDetailsFromPush($payload);
    }
    if (!$this->branch) {
      throw new \Exception("Unable to determine branch from payload data");
    }
    if (!$this->repo) {
      throw new \Exception("Unable to determine repository from payload data");
    }
  }

  /**
   * Use a pull request to figure out what branch and repo we are talking
   * about, and the also work out what emails we should send.
   *
   * @param mixed $payload An object given by json_decode()
   */
  public function getDetailsFromPullRequest($payload) {
    if ($payload->action != 'closed') {
      throw new \Exception("Pull request is not closed");
    }
    if (!$payload->pull_request->merged) {
      throw new \Exception("Pull request is not merged");
    }
    $this->branch = $payload->pull_request->base->ref;
    $this->repo = $payload->repository->html_url;

    //Get emails of people that should be notified
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $payload->pull_request->commits_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Content-type: application/json',
      'User-Agent: civicrm-docs',
        )
    ); // Assuming you're requesting JSON
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $this->commits = json_decode(curl_exec($ch));

    foreach ($this->commits as $commit) {
      $this->addRecipients($commit->commit->author->email);
      $this->addRecipients($commit->commit->committer->email);
    }
  }

  /**
   * Use a pull request to figure out what branch and repo we are talking
   * about, and the also work out what emails we should send.
   *
   * @param mixed $payload An object given by json_decode()
   */
  public function getDetailsFromPush($payload) {
    $this->branch = preg_replace("#.*/(.*)#", "$1", $payload->ref);
    $this->repo = $payload->repository->html_url;
    foreach ($payload->commits as $commit) {
      $this->addRecipients($commit->author->email);
      $this->addRecipients($commit->committer->email);
    }
  }

  /**
   * Adds one or more email recipients, and makes sure all recipients are
   * kept unique
   *
   * @param array $recipients Array of strings for emails of people to notify
   */
  public function addRecipients($recipients) {
    if (!is_array($recipients)) {
      $recipients = array($recipients);
    }
    // remove any email addresses begins with "donotreply@" or "noreply"
    $recipients = preg_grep('/^(donot|no)reply@/', $recipients, PREG_GREP_INVERT);
    
    $this->recipients = array_unique(array_merge($this->recipients, $recipients));
  }

}
