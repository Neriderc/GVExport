![image](https://img.shields.io/github/downloads/Neriderc/GVExport/total) ![image](https://img.shields.io/github/downloads/Neriderc/GVExport/latest/total)

GVExport
========

This is a repository for a webtrees module known as GVExport. GVExport's goal is to produce an "all in one tree" of every person in a family tree and their relationships. You can view the tree in webtrees and click on individuals of families to go to their page in webtrees, and produce output in multiple file formats such as JPG, SVG, PNG, PDF, and if Graphviz is installed on the server, others are possible.

One of the display options renders output like this:

![image](https://user-images.githubusercontent.com/89974636/188861665-e2578650-071e-46fa-a935-e510ae28c617.png)

Webtrees
--------

[Webtrees](https://webtrees.net/) is an online collaborative genealogy application. This can be hosted on your own server by following the [Install instructions](https://webtrees.net/install/). If you are familiar with Docker, you might like to install Webtrees using [this unofficial docker image](https://hub.docker.com/r/nathanvaughn/webtrees), [or this one](https://github.com/H2CK/webtrees).

Installation of GVExport
------------

To install GVExport, download the latest [release](https://github.com/Neriderc/GVExport/releases), copy files to modules_v4 directory in webtrees and rename the folder to GVExport. For more detail, see the [install instructions](https://github.com/Neriderc/GVExport/wiki/Installing-the-module).

GVExport Module
---------------

Once installed (and enabled), go to *Charts -> GVExport* to see the options.

See [How to use GVExport](https://github.com/Neriderc/GVExport/wiki/How-to-use-GVExport) for help using GVExport for the first time.

For more information on the options, see [Detailed information on each option](https://github.com/Neriderc/GVExport/wiki/Detailed-information-on-each-option).

Help and support
----------------

Information on using GVExport can be found in [the wiki](https://github.com/Neriderc/GVExport/wiki). A German wiki is available [here](https://wiki.genealogy.net/Webtrees_Handbuch/Anleitung_f%C3%BCr_Webmaster/Erweiterungsmodule/GVExport).

If you need help with GVExport, please [create an issue](https://github.com/Neriderc/GVExport/issues) on GitHub.

If you find a bug or something isn't working, please [create an issue](https://github.com/Neriderc/GVExport/issues).

If you want to request a new feature, please [create an issue](https://github.com/Neriderc/GVExport/issues).

Older Versions
--------------

We attempt to keep up with the latest stable (non-prerelease) version of webtrees. You can see the latest release of webtrees [here](https://github.com/fisharebest/webtrees/releases). Older versions are not supported. *However*, as we progress through webtrees versions, we are releasing GVExport versions. These are listed on the [Releases page](https://github.com/Neriderc/GVExport/releases). If you are not using the latest version of webtrees, and the latest release of GVExport isn't working for you, you may want to try an older release.

For versions earlier than 2.0.21 you can try the [source repository](https://github.com/tillsc/GVExport), but note this DOES NOT work with PHP 8 and it is missing many enhancements from this repository.

Contributing
------------

If you'd like to contribute to GVExport, great! You can contribute by:

- Creating issues for any new ideas you have
- Creating issues if you find bugs
- Contributing code - check out the Issues for things that need attention. If you have changes you want to make not listed in an issue, please create one, then you can link your pull request.
- Testing - please [create an issue](https://github.com/Neriderc/GVExport/issues) for any bugs you find. If you're familiar with Playwright, [create an issue](https://github.com/Neriderc/GVExport/issues) and volunteer to build on our current basic automated tests.
- Documentation - anyone can update [our Wiki](https://github.com/Neriderc/GVExport/wiki)
- Translating - you can translate into a language you are fluent in by joining the [PO Editor project](https://poeditor.com/join/project/YqPRBXZnlf). Discussion on translating can be done by creating an issue.

For more information on contributing, please see [CONTRIBUTING.md](https://github.com/Neriderc/GVExport/blob/master/CONTRIBUTING.md).

This repository
---------------
This repository was forked from [tillsc/GVExport](https://github.com/tillsc/GVExport) after community pull requests had not been merged for some time. [A post](https://www.webtrees.net/index.php/en/forum/4-customising/35801-display-complex-all-in-on-tree-with-gvexport) had been made on the webtrees forum stating that GVExport had been brought up to date to work with webtrees 2.0, but that the developer could not provide ongoing support. After a webtrees update broke the module, this fork was created to maintain compatibility and continue refining the tool with new features and other enhancements.

Current maintainer:
 - https://github.com/Neriderc
(plus community contributions)

Past Authors:

 - Ferenc Kurucz <korbendallas1976@gmail.com>
 - https://github.com/IJMacD
 - https://github.com/pceres
 - Till Schulte-Coerne (https://github.com/tillsc)


