<?php

namespace AppBundle\Utils;

class GitHubHookProcessor {

  /**
   * @var string the URL for the repository
   */
  public $repo;

  /**
   * @var string the name of the branch to publish
   */
  public $branch;

  /**
   * Process a GitHub webhook
   *
   * @param string $event
   *   e.g. 'pull_request', 'push'
   *
   * @param mixed $payload
   *   An object given by json_decode()
   *
   * @return array
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

    return $this->getDetailsFromPush($payload);
  }

  /**
   * Use a "push" event to figure out what branch and repo we are talking
   * about, and the also work out what emails we should send.
   *
   * @param mixed $payload
   *   An object given by json_decode()
   *
   * @return array
   *
   * @throws \Exception
   */
  protected function getDetailsFromPush($payload) {
    $this->branch = preg_replace("#.*/(.*)#", "$1", $payload->ref);
    if (empty($this->branch)) {
      throw new \Exception("Unable to determine branch from payload data");
    }
    $this->repo = $payload->repository->html_url;
    if (empty($this->repo)) {
      throw new \Exception("Unable to determine repository from payload data");
    }

    $recipients = [];
    foreach ($payload->commits as $commit) {
      $this->addRecipients($recipients, $commit->author->email);
      $this->addRecipients($recipients, $commit->committer->email);
    }

    return [
      'commits' => $payload->commits,
      'recipients' => $recipients
    ];
  }

  /**
   * Adds one or more email recipients, and makes sure all recipients are
   * kept unique
   *
   * @param array $new
   *   Array of strings for emails of people to notify
   * @param array $existing
   *   Existing recipients so far
   * @return array
   *   Unique array of recipients
   */
  protected function addRecipients($existing, $new) {
    if (!is_array($new)) {
      $new = array($new);
    }
    // remove any email addresses begins with "donotreply@" or "noreply"
    $new = preg_grep('/^(donot|no)reply@/', $new, PREG_GREP_INVERT);

    return array_unique(array_merge($existing, $new));
  }
}
