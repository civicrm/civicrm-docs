## CiviCRM documentation infrastructure

A summary of CiviCRM's documentation infrastructure.

## Accessing documentation

CiviCRM documentation is currently found in many different sources. Our long term vision is to have it all published at https://docs.civicrm.org/.

Documentation is organised into books, which are accessible at URLs as follows https://docs.civicrm.org/[name]/  By default, a URL in the above format will redirect to https://docs.civicrm.org/[name]/en/stable which shows the latest stable documentation in English.

Books may also be available in different lanaguages and have different editions (e.g. 4.6, 4.7) (which typically map to the version of CiviCRM that they are documenting.

The very latest documentation (which may be incomplete / unfinished) can be accessed at https://docs.civicrm.org/[name]/en/latest.

## Contributing to documentation

All CiviCRM documentation should be written in Markdown, following [mkdocs](http://www.mkdocs.org) conventions, and stored in a git repository, such as https://github.com/civicrm/civicrm-user-guide.

See https://civicrm.org/improve-documentation for how to get started contributing to our documentation. If you have any questions about how stuff works, or how to start contributing, please join the [documentation mailing list](http://lists.civicrm.org/lists/info/civicrm-docs) and email the list with your question. We'll be very glad to help you get going.

## Updating documentation

Books are auto be updated when the corresponding branch is updated their repository. This is typically acheived by making edits and submitting a pull request. Any emails listed in the commits that are submitted as part of the pull request will receive an email with a summary of the update process.

If required, a book can be manually updated by calling a URL in the following format: https://docs.civicrm.org/admin/publish/{book}/{en}/{branch}.

* {book} the name of the book - as per configuration file in the conf/books directory.
* {lang} the language that you want to publish - as defined in the configuration file.
* {branch} the name of the branch that you want to publish - needs to be a branch in the repository for that language.

## Defining books

Books are defined with a Yaml configuration file, stored in the app/config/books/ directory of this repository.

The config file lists the **languages** that the book is available in, with a repository for each language. For each language, the configuration file defines:

* which **edition** of the book should be considered **stable**
* where to find the **latest** edits to the book
* a history of book **editions** of the book (these will be publicly listed at https://docs.civicrm.org/).

# Auto updating documentation

Auto updates are configured via github webhooks.

1. Go to https://github.com/civicrm/[repo-name]/settings/hooks/new
2. Set the **Payload URL** to 'http://docs.civicrm.org/admin/listen'
3. Set the **Content type** to 'application/json'
3. Set the **Secret** to match the secret as defined in app/config/parameters.yml
4. Set **Which events would you like to trigger this webhook?** to 'Let me select individual events' and select 'Pull request' and 'Push'

Note: automatic updates currently only happen after a pull request is merged.

# Installation

**Note**: the following steps are only useful and necessary for people looking after CiviCRM's documentation infrastructure.. If you are want to contribute to CiviCRM's documentation, there see [Contributing to documentation](#contributing-to-documentation) above.

1) Ensure that that you have [pip](https://packaging.python.org/en/latest/install_requirements_linux/#installing-pip-setuptools-wheel-with-linux-package-managers) (for python) and [composer](https://getcomposer.org/) (for php) installed..

2) Install mkdocs (`$ sudo pip install mkdocs`). ***Note:*** *Ensure that mkdocs is installed as root so that it can be accessed from the src/publish.php script (typically invoked as https://docs.civicrm.org/publish.php)*

3) clone this repository to somewhere like /var/www/civicrm-docs and run `composer install`

```
$ git clone /var/www/civicrm-docs
$ cd /var/www/civicrm-docs
$ composer install
```

4) Set appropriate permissions on web/static

5) Configure an nginx virtual host

```
$ cd /etc/nginx/sites-enabled
$ ln -s /var/www/civicrm-docs/app/config/nginx.conf civicrm-docs
```

6) Reload your nginx config and you should be up and running.

# To do

* Clean up our various documentation sources, delete ones that are not in use (adding redirects to docs.c.o), (book.civicrm.org, http://civicrm-user-guide.readthedocs.org/en/latest/, etc.)
* Report if the git pull goes wrong
* Update UI documentation linksar
* add per book locking / queue for publishing
* document how to add a webhook to your repo.
* create pdf and ePub versions of the document when publishing (maybe using pandoc)
* find a nice userfriendly UI for people to edit the documentation (the github UI is OK but we can do better)
* should doc infra interact with extension info.xml files?
* A CiviCRM theme for documentation
    * which includes the civicrm version
* Book validation
    * missing images (`ack '\!\[.*\]\((.*?)( ".*)?\)' -h --nobreak --output='$1'` will give all images)
* Future proof documentation structure
    * / - documentation home
    * /user/ - user documentation for core civicrm
    * /extensions/[extenstion_name] - documentation for extensions (probably mostly user focused, though with possible developer and system administrator sections)
    * /admin/ - system administrator documentation (installation, upgrades, email server configuration)
    * /dev/ - developer documentation
* Last but not least: migrate lots of documentation (e.g. our developer and sys administrator documentation)
