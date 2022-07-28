# Introduction

First off, thank you for considering contributing to GVExport. This module is the work of many contributors so it's people like you that make this module possible.

There are many ways to contribute, such as:
- Creating issues for any new ideas you have
- Creating issues if you find bugs
- Contributing code
- Testing
- Documentation
- Translating

# Index
- [Questions](https://github.com/Neriderc/GVExport/blob/master/CONTRIBUTING.md#questions)
- [Documentation](https://github.com/Neriderc/GVExport/blob/master/CONTRIBUTING.md#documentation)
- [Translating](https://github.com/Neriderc/GVExport/blob/master/CONTRIBUTING.md#translating)
- [Contributing code](https://github.com/Neriderc/GVExport/blob/master/CONTRIBUTING.md#contributing-code)
- [Testing](https://github.com/Neriderc/GVExport/blob/master/CONTRIBUTING.md#testing)
- [How to report a bug](https://github.com/Neriderc/GVExport/blob/master/CONTRIBUTING.md#how-to-report-a-bug)
- [How to suggest a feature or enhancement](https://github.com/Neriderc/GVExport/blob/master/CONTRIBUTING.md#how-to-suggest-a-feature-or-enhancement)

# Questions

For questions on using GVExport, in the first instance please see [our Wiki](https://github.com/Neriderc/GVExport/wiki).

If you still have questions, please [create an issue](https://github.com/Neriderc/GVExport/issues). This is the main method for any contact (for security issues please see [SECURITY.md](https://github.com/Neriderc/GVExport/blob/master/SECURITY.md)). The project is small and niche enough that we don't need separate discussions and issues. We don't currently have a [Matrix](https://matrix.org/) room, but could consider this if there is enough interest in real time chats and questions.

# Documentation

Currently we use a wiki for documenting functionality. Anyone can update [our Wiki](https://github.com/Neriderc/GVExport/wiki) so if you see changes that need doing or feel a new page would help, feel free to do it. If you are not sure about something or want to discuss changes with the communitry, please [create an issue](https://github.com/Neriderc/GVExport/issues).

# Translating

You can translate into a language you are fluent in by joining the [PO Editor project](https://poeditor.com/join/project/YqPRBXZnlf). 

If you're not familiar with contributing on GitHub, see [this tutorial](https://github.com/firstcontributions/first-contributions).

If you're familiar with GitHub, please create a pull request to add your languages to the module. The basic instructions for this are:

- In POEditor, click on your language, and then at the top (underthe header) choose "Export"
- You'll need to export two files. Both a ".mo" and a ".po" file. The ".mo" is the machine-readable file that webtrees uses for translating. The ".po" file is a human-readable version that allows us to keep a history of changes in the GIT repository (which we can re-import to POEditor if needed).
- These files need to be named after the language code for the language they are. For example, for Spanish we should have "es.po" and "es.mo". This should generally be the 2-letter code [listed here](https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes).
- Fork the repository and add the files to the resources/lang/ folder.
- Create a pull request to submit the change back to this repository.

This is the preferred way to receive a contribution as it allows you to be recognised in GitHub as a contributor. If you're having trouble, feel free to [create an issue](https://github.com/Neriderc/GVExport/issues) to get some help.

If you update a language in POEditor but don't create a pull request, we will download the language files and add them to the project as the next version is released.

Discussion on translating can be done by [creating an issue](https://github.com/Neriderc/GVExport/issues).

# Contributing code

Please note this project is GLP 2.0 licenced, and all contributions are under the same liecnce. Please ensure all work is your own.

If you're new to contributing, see [this tutorial](https://github.com/firstcontributions/first-contributions) on contributing to projects on GitHub.

Check out the [Issues](https://github.com/Neriderc/GVExport/issues) for things that need attention. 

If you have changes you want to make not listed in an issue, please create one, then you can link your pull request.

Please also see the [Project Board](https://github.com/users/Neriderc/projects/1/views/1) to help find issues that should have all required information (in the "Ready" column), and to help avoid working on things already in progress. In additon, check if an issue is assigned to someone already.

There are no code style guidelines. The code was inherited as bits an pieces from various authors, and needs lots of tidying. If you'd like to volunteer for this task, feel free :).

# Testing

It's all manual currently, please [create an issue](https://github.com/Neriderc/GVExport/issues) for any bugs you find.

If you're keen and available to do testing of pull requests or regression testing before releases, please [create an issue](https://github.com/Neriderc/GVExport/issues) and volunteer.

We would also hapily accept automated tests if that's your thing.

# How to report a bug
If you find a security vulnerability, do NOT open an issue. See [SECURITY.md](https://github.com/Neriderc/GVExport/blob/master/SECURITY.md)) for the email address to contact.

For any other bugs or potential bugs, please [create an issue](https://github.com/Neriderc/GVExport/issues).

# How to suggest a feature or enhancement

GVExport is a module for webtrees, that is used to visualise a family tree and allows you to download the visualisation in various file formats. This repository is making many changes over the previous abandoned repo that it was forked from, but with some ideas in mind:

- Changes should not remove existing functionality
- New features are great, but the tool should work for both beginner and advanced users. The options should be powerful but not confusing. We intend to move many options to the control panel settings, to help separate advanced features from the regular UI.
- Currently downloading output requires GraphViz to be installed on the server. Our road map includes reducing this dependancy.
- Our roadmap also includes quality of life changes such as improving error messages and adding validation to fields to prevent submitting values that don't make sense.

Please check the [issues list](https://github.com/Neriderc/GVExport/issues) and the [project board](https://github.com/users/Neriderc/projects/1/views/1) to see what has been discussed and what is on the roadmap. Any ideas that aren't specifically mentioned can be added by creating a new issue.

