
# Usage

All CiviCRM documentation should be written in Markdown, following [mkdocs](http://www.mkdocs.org) conventions, and stored in a git repository, such as https://github.com/civicrm/civicrm-user-guide.

Documentation is organised into 'books'.  Each book can be made available in different lanaguages. Each language can have different editions.

Editions of books typically map to versions of CiviCRM (or extensions that they document). Editions are created by creating a branches in each language repository.

## Contributing to documentation

See https://civicrm.org/improve-documentation for how to get started contributing to our documentation. If you have any questions about how stuff works, or how to start contributing, please join the [documentation mailing list](http://lists.civicrm.org/lists/info/civicrm-docs) and email the list with your question. We'll be very glad to help you get going.

## Publishing books

All books are published at https://docs.civicrm.org/[name]/

By default, a URL in the above format will redirect to https://docs.civicrm.org/[name]/en/stable.

Some time in the near future, books will automatically be updated when the corresponding branch is updated in the repository.

Until that happens, the update process can be manually triggered by calling a URL in the following format:

https://docs.civicrm.org/build.php?book=user&branch=master&lang=en

With parameters set as follows: 

* **book**: the name of the book - as per configuration file in the conf/books directory.
* **lang**: the language that you want to build - as defined in the configuration file.
* **branch**: the name of the branch that you want to publish - needs to be a branch in the repository for that language.

### Trouble shooting the publishing process

Once the build process is complete, any output from the build process (mkdocs build) is shown on the screen.

If nothing is shown on the screen, some rudimentary logging available at https://docs.civicrm.org/log might give you a clue as to what happened.

**Tip:** Using 'view source' in your browser will show line breaks for these logs.

## Defining books

Books are defined with a Yaml configuration file, stored in the conf/books directory of this repository.

The config file lists the **languages** that the book is available in, with a repository for each language. For each language, the configuration file defines:

* which **edition** of the book should be considered **stable**
* where to find the **latest** edits to the book
* a history of book **editions** of the book (that will be publicly listed).

An example configuration file for the CiviCRM user guide ('user.yml'):

```Yaml
en:
 repo: 'https://github.com/civicrm/civicrm-user-guide'
 latest: master
 stable: 4.7
 history:
   - 4.7
   - 4.6
```

# Installation

**Note**: the following steps are only useful and necessary for people looking after CiviCRM's documentation infrastructure. If you want to contributing to CiviCRM's documentation, see [Contributing to documentation](#contributing-to-documentation) above.

1) Ensure that that you have [pip](https://packaging.python.org/en/latest/install_requirements_linux/#installing-pip-setuptools-wheel-with-linux-package-managers) (for python) and [composer](https://getcomposer.org/) (for php) installed..

2) Install mkdocs.

```
$ sudo pip install mkdocs
```
***Note:*** *Ensure that mkdocs is installed as root so that it can be accessed from the src/build.php script (typically invoked as https://docs.civicrm.org/build.php)*

3) clone this repository to somewhere like /var/www/civicrm-docs

3) Run composer install

```
$ cd /var/www/civicrm-docs
$ composer install
```

4) Run the civicrm-docs install script

```
$ cd /var/www/civicrm-docs
$ ./install.sh
```

5) Configure an nginx virtual host

```
$ cd /etc/nginx/sites-enabled
$ ln -s /var/www/civicrm-docs/conf/nginx.conf civicrm-docs
```

6) Reload your nginx config and you should be up and running.

# To do

* A CiviCRM theme for documentatiom
* Create a better docs homepage
* Automate creation of symlinks for latest and stable based on book.yml file
* Create history for each book based on book.yml file
* Improve nginx.conf
    * Remove the trailing slash from URLs
    * user|developer|etc should not be hard coded
    * avoid root in location block. Might need to rethink dir structure (https://www.nginx.com/resources/wiki/start/topics/tutorials/config_pitfalls/#root-inside-location-block)
* Last but not least: migrate lots of documentation (e.g. our developer and sys administrator documentation)
* Future proof documentation structure
    * / - documentation home
    * /user/ - user documentation for core civicrm
    * /extensions/*/ - documentation for extensions (probably mostly user focused, though with possible developer and system administrator sections)
    * /admin/ - system administrator documentation (installation, upgrades, email server configuration)
    * /dev/ - developer documentation
