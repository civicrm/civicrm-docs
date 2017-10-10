<?php

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SwiftmailerBundle\DataCollector\MessageDataCollector;
use Symfony\Component\HttpFoundation\Response;

class PublishControllerTest extends WebTestCase {

  /**
   * Check that listen endpoint is working and sends mail
   */
  public function testListenAction() {
    $client = static::createClient();
    $client->enableProfiler();

    $hookBody = $this->getGithubRequestBody();
    $headers = $this->getHeaders();
    $endpoint = '/admin/listen';

    $client->request('POST', $endpoint, [], [], $headers, $hookBody);
    $statusCode = $client->getResponse()->getStatusCode();

    $this->assertEquals(Response::HTTP_OK, $statusCode);

    /** @var MessageDataCollector $mailCollector */
    $mailCollector = $client->getProfile()->getCollector('swiftmailer');
    /** @var \Swift_Message[] $mails */
    $mails = $mailCollector->getMessages();
    $this->assertCount(1, $mails);

    $hookData = json_decode($hookBody, true);
    $sampleCommitHash = current($hookData['commits'])['id'];
    $sentMessage = array_shift($mails);

    $this->assertContains('Publishing Successful', $sentMessage->getBody());
    $this->assertContains($sampleCommitHash, $sentMessage->getBody());
  }

  /**
   * @return string
   */
  private function getGithubRequestBody(): string {
    return file_get_contents(__DIR__ . '/../Files/webhook-push-sample.json');
  }

  /**
   * @return array
   */
  private function getHeaders(): array {
    $headers = [
      'HTTP_X-GitHub-Event' => 'push', // prefix required for non-standard
      'Content-Type' => 'application/json'
    ];

    return $headers;
  }

}
