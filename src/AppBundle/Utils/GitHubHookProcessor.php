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
   * Constructor
   */
  public function __construct() {

  }

  /**
   * Process a GitHub webhook
   *
   * @param string $event
   *   e.g. 'pull_request', 'push'
   *
   * @param mixed $payload
   *   An object given by json_decode()
   *
   * @throws \Exception
   */
  public function process($event, $payload) {
    if (empty($payload)) {
      throw new \Exception("No payload data supplied");
    }
    if (empty($event)) {
      throw new \Exception("Unable to determine webhook event type from "
          . "request headers");
    }
    if ($event != 'push') {
      throw new \Exception("Webhook event type is not 'push'");
    }
    $this->getDetailsFromPush($payload);
  }

  /**
   * Use a "push" event to figure out what branch and repo we are talking
   * about, and the also work out what emails we should send.
   *
   * @param mixed $payload
   *   An object given by json_decode()
   *
   * @throws \Exception
   */
  public function getDetailsFromPush($payload) {
    $this->branch = preg_replace("#.*/(.*)#", "$1", $payload->ref);
    if (empty($this->branch)) {
      throw new \Exception("Unable to determine branch from payload data");
    }
    $this->repo = $payload->repository->html_url;
    if (empty($this->repo)) {
      throw new \Exception("Unable to determine repository from payload data");
    }
    foreach ($payload->commits as $commit) {
      $this->addRecipients($commit->author->email);
      $this->addRecipients($commit->committer->email);
    }
  }

  /**
   * Adds one or more email recipients, and makes sure all recipients are
   * kept unique
   *
   * @param array $recipients
   *   Array of strings for emails of people to notify
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
