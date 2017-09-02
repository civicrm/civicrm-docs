<?php

namespace AppBundle\Utils;

use Symfony\Component\Process\Process;

class GitTools {
  /**
   * @param $repoPath
   *
   * @return string
   */
  public function getCurrentBranch($repoPath): string {
    $checkBranch = new Process('git rev-parse --abbrev-ref HEAD', $repoPath);
    $checkBranch->run();
    $currentBranch = trim($checkBranch->getOutput());

    return $currentBranch;
  }

  /**
   * @param string $repoPath
   */
  public function pull($repoPath) {
    $gitPull = new Process('git pull', $repoPath);
    $gitPull->run();

    if (!$gitPull->isSuccessful()) {
      $msg = "Unable to run 'git pull'. Output: " . $gitPull->getErrorOutput();
      throw new \Exception($msg);
    }
  }

  /**
   * @param $repoURL
   * @param $repoPath
   *
   * @throws \Exception
   */
  public function clone($repoURL, $repoPath) {
    $process = new Process("git clone {$repoURL} {$repoPath}");
    $process->run();

    if (!$process->isSuccessful()) {
      $msg = "Unable to run 'git clone'. Output: " . $process->getErrorOutput();
      throw new \Exception($msg);
    }
  }

  /**
   * @param $repoPath
   *
   * @throws \Exception
   */
  public function fetch($repoPath) {
    $process = new Process('git fetch', $repoPath);
    $process->run();

    if (!$process->isSuccessful()) {
      $msg = "Unable to run 'git fetch'. Output: " . $process->getErrorOutput();
      throw new \Exception($msg);
    }
  }

  /**
   * If we are on the not on the correct branch, attempt to check it out
   * (first locally, then remotely).
   *
   * @param string $repoPath
   * @param string $branch
   */
  public function checkout(string $repoPath, string $branch) {
    $currentBranch = $this->getCurrentBranch($repoPath);

    if ($currentBranch != $branch) {
      if (!$this->localBranchExists($repoPath, $branch)) {
        if (!$this->remoteBranchExists($repoPath, $branch)) {
          $err = "'{$branch}' branch does not exist " . "remotely or locally.";
          throw new \Exception($err);
        }
      }

      $process = new Process("git checkout {$branch}", $repoPath);
      $process->run();

      if (!$process->isSuccessful()) {
        $err = "Unable to run 'git checkout'\n" . $process->getErrorOutput();
        throw new \Exception($err);
      }
    }
  }

  /**
   * @param $repoPath
   * @param $branch
   *
   * @return bool
   */
  private function localBranchExists($repoPath, $branch) {
    $cmd = "git show-ref --verify refs/heads/{$branch}";
    $process = new Process($cmd, $repoPath);
    $process->run();

    return $process->isSuccessful();
  }

  /**
   * @param $repoPath
   * @param $branch
   *
   * @return bool
   */
  private function remoteBranchExists($repoPath, $branch) {
    $cmd = sprintf("git show-ref --verify refs/remotes/origin/{$branch}");
    $process = new Process($cmd, $repoPath);
    $process->run();

    return $process->isSuccessful();
  }
}
