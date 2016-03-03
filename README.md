
# Usage

All CiviCRM documentation should be written in Markdown, following [mkdocs](http://www.mkdocs.org) conventions, and stored in a git repository.

## Defining books

Books are defined with a Yaml configuration file, stored in the conf/books directory of this repository.

The config file lists the **languages** that the book is available in, with a repository for each language. For each language, the file defines:

* which edition of the book should be considered stable
* where to find the latest edits to the book
* a history of book editions of the book.

Editions of books typically editions map to versions of CiviCRM (or in the case of extensions, the version of the extension).  Editions are created by creating corresponding branches in the repository.

Example Yaml file for our user guide: 'user.yml'

```Yaml
en:
 - repo: 'https://github.com/civicrm/civicrm-user-guide'
 - latest: master
 - stable: 4.7
 - history:
   - 4.7
   - 4.6
```

## Publishing books

Some time in the near future, books will automatically be updated when the corresponding branch is updated in the repository.

Until that happens, the update process can be manually triggered by calling a URL in the following format:

http://docs/build.php?book=user&branch=master&lang=en

## Trouble shooting

Some rudimentary logging is available at https://docs.civicrm.org/log.

**Note:** these are plain text files and not that pretty right now. Using 'view source' in your browser is one way to make them easier to read.

# Requirements

* pip (sudo apt-get install python-pip)
* composer (https://getcomposer.org/)

# Installation

1) Install mkdocs.


```
$ sudo pip install mkdocs
```
***Note:*** *Ensure that mkdocs is installed as root so that it can be accessed from the src/build.php script (typically invoked as https://docs.civicrm.org/build.php)*

2) Run composer install

```
$ cd /var/www/civicrm-docs
$ composer install
```

3) Run the civicrm-docs install script

```
$ cd /var/www/civicrm-docs
$ ./install.sh
```

4) Configure an nginx virtual host

```
$ cd /etc/nginx/sites-enabled
$ ln -s /var/www/civicrm-docs/conf/nginx.conf civicrm-docs
```
