[![codecov](https://codecov.io/gh/sitepark/composer-project/branch/main/graph/badge.svg?token=68Mk5j1BY3)](https://codecov.io/gh/sitepark/composer-project)

`composer-project` is a composer plugin for managing a project workflow based on this [Branching model](https://sitepark.github.io/github-project-workflow/branching-model.html)

Features:

* Determine the version of the active Git branch.
* Determination of the next version of the active Git branch.
* Checks if all dependencies are stable and the active git branch can be released.
* Create a hotfix branch from a released version.
* Create a release

`composer-project` supports the following branches

* `main`
* `support/[MAJOR].x`
* `hotfix/[MAJOR].[MINOR].x`


## Install

The plugin is intended to be used in a CI/CD environment and is installed there globally.

```sh
$ composer global require "sitepark/composer-project" --dev
```

## Usage

After `composer-project` is installed, the following commands are available in the global composer:

* `composer project:version` - Outputs the current version of the Git branch
* `composer project:releaseVersion` - Outputs the next release version of the current Git branch. Here to the version of the last release of the Git branch is determined and for the branches `main` and `support/[MAJOR].x` the minor version is incremented by one. For branches of the form `hotfix/[MAJOR].[MINOR].x` the patch level is incremented by one.
* `composer project:verifyRelease` - It is checked whether the current branch is releasable. For this it is added whether all dependencies were defined with a stable version.
* `composer project:startHotfix` - Creates a hotfix branch of the form `hotfix/[MAJOR].[MINOR].x` based on the current checked out level, where this should be a tag level. the Current tag level should always be the latest patch level of a given minor version. This version is determined and based on this version the Hotifx branch is named.
* `composer project:release` - Here the next release version of the current branch is determined and a tag of the form `[MAJOR].[MINOR].[PATCH]` is created. Releases can be created for the branches `main`, `support/[MAJOR].x` and `hotfix/[MAJOR].[MINOR].x`.


### `project:verifyRelease`

You can use `project:verifyRelease` to check whether the current branch is releasable. This checks whether all dependencies have been defined with a stable version.

The stability of the versions is checked via `Composer\Semver\VersionParser:parseStability`. Possible values are: `dev`, `alpha`, `beta` `RC` or `stable`. The branch can only be released if all dependencies have been defined with the stability `stable`.

Use

```sh
composer show
```

To display all dependencies and their versions.

In exceptional cases, it may be necessary to create a release even though dependencies have been defined with non-stable versions. For example, the following configuration can be set for the project in the `composer.json` file:

```json
{
   "extra" : {
        "verify-release" : {
                "allowed-stabilities" : ["beta"]
        }
   }
}
```

In this case, `beta` versions are also permitted. Several stability levels can be defined.
