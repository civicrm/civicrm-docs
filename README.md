# CiviCRM documentation infrastructure

This repository holds source code for the *infrastructure* CiviCRM uses to host and update various documentation books built with [MkDocs](http://mkdocs.org/) and published to [docs.civicrm.org](https://docs.civicrm.org).

You may also wish to:

- [*Read* these documentation books](https://civicrm.org/documentation) *(and other sources of documentation)*
- [*Contribute* to documentation content](https://docs.civicrm.org/dev/en/master/documentation/)


## Documentation books

CiviCRM documentation is organised into various *books*. The content for a book is written in [markdown](https://docs.civicrm.org/dev/en/master/markdownrules/) and stored in a git repository (for example https://github.com/civicrm/civicrm-user-guide ). If a book is translated into different languages, then a separate repository is used for each language. If required, different *versions* of a book can be made by creating different *branches* in a repository. See *Defining books* below for more information.

## Defining a new book

Books are defined with a Yaml configuration file. To define a new book, create a `.yml` file and add it to the `app/config/books/` directory of this repository.

The config file defines the name of the book, the repository that contains the source code, and the **languages** that the book is available in, with a repository for each language. For each language, the configuration file defines:

* which **edition** of the book should be considered **stable**
* where to find the **latest** edits to the book
* a history of book **editions** of the book (these will be publicly listed at https://docs.civicrm.org/).

If you would like to be notified by email whenever an update to a book is published, you can add your email to the notify list.

**Example book definition:**
```yml
name: User guide
description: Aimed at day to day users of CiviCRM.
langs:
    en:
        repo: 'https://github.com/michaelmcandrew/civicrm-user-guide'
        latest: master
        stable: 4.7
        history:
            - 4.6
            - 4.5
            - 4.4
        notify:
            michael@civicrm.org # will be notified when documentation is published (as well as any emails mentioned in commits)
    ca:
        repo: 'https://github.com/babu-cat/civicrm-user-guide-ca'
        latest: master
        # stable: master (will not be published)
```

## Publishing updates to a book

Books are automatically published when the corresponding branch is updated their repository. This is typically achieved by making edits and submitting a pull request. Any emails listed in the commits that are submitted as part of the pull request will receive an email with a summary of the update process.

### Setting up automatic publishing

Auto updates are configured via webhooks within the repository on GitHub. You will need to be an owner (not just a collaborator) of the repository in order to perform these steps.

1. Go to `https://github.com/civicrm/[repo-name]/settings/hooks/new`
1. Set the **Payload URL** to https://docs.civicrm.org/admin/listen
1. Set the **Content type** to 'application/json'
1. Set **Which events would you like to trigger this webhook?** to 'Let me select individual events' and select 'Pull request' and 'Push' (since these are the only events that should trigger an update)

### Manual publishing

If required, a book can be manually updated by calling a URL in the following format.

```text
https://docs.civicrm.org/admin/publish/{book}/{lang}/{branch}
```

* `{book}` the name of the book - as per configuration file in the conf/books directory.
* `{lang}` the language that you want to publish - as defined in the configuration file.
* `{branch}` the name of the branch that you want to publish - needs to be a branch in the repository for that language.


## Installing a local copy of the docs infrastructure

**Note**: the following steps are only useful and necessary for people looking after CiviCRM's documentation *infrastructure*. You don't need to do this if you just want to [contribute to documentation content](https://docs.civicrm.org/dev/en/master/documentation/).

1. Ensure that that you have [pip](https://packaging.python.org/en/latest/install_requirements_linux/#installing-pip-setuptools-wheel-with-linux-package-managers) (for python) and [composer](https://getcomposer.org/) (for php) installed..

2. Install MkDocs (`$ sudo pip install mkdocs`). ***Note:*** *Ensure that MkDocs is installed as root so that it can be accessed from the src/publish.php script (typically invoked as https://docs.civicrm.org/publish.php)*

3. clone this repository to somewhere like /var/www/civicrm-docs and run `composer install`

        $ git clone /var/www/civicrm-docs
        $ cd /var/www/civicrm-docs
        $ composer install

4. Set appropriate permissions on web/static

5. Configure an nginx virtual host

        $ cd /etc/nginx/sites-enabled
        $ ln -s /var/www/civicrm-docs/app/config/nginx.conf civicrm-docs

6. Reload your nginx config and you should be up and running.

