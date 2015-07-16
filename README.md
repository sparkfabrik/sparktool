[![Code Climate](https://codeclimate.com/github/sparkfabrik/sparktool/badges/gpa.svg)](https://codeclimate.com/github/sparkfabrik/sparktool)
[![Test Coverage](https://codeclimate.com/github/sparkfabrik/sparktool/badges/coverage.svg)](https://codeclimate.com/github/sparkfabrik/sparktool/coverage)
[![Travis Build](https://api.travis-ci.org/sparkfabrik/sparktool.svg?branch=develop)](https://travis-ci.org/sparkfabrik/sparktool)

# Table of Contents

* [Synopsis](#synopsis)
* [Team Members](#team-members)
* [Installation](#installation)
* [Usage](#usage)

# <a name="team-members"></a>Synopsis

[Explan the project]

# <a name="team-members"></a>Team Members

https://github.com/orgs/sparkfabrik/people

#<a name="installation"></a>Installation

Download the latest release:

```
wget -O /usr/local/bin/spark https://raw.githubusercontent.com/sparkfabrik/sparktool/release/spark.phar
```

Or just clone the project from: `git@github.com:sparkfabrik/sparktool.git`


[TBD]

#<a name="usage"></a>Usage

##Redmine

###Search issues by date

```
Ranges

./spark.php redmine:search --assigned="paolo mainardi" --updated="1 week ago" --updated="today"

Single

./spark.php redmine:search --assigned="paolo mainardi" --updated="1 week ago"

Single after a date

./spark.php redmine:search --assigned="paolo mainardi" --updated=">= 1 week ago"

Single before a date

./spark.php redmine:search --assigned="paolo mainardi" --updated="<= 1 week ago"
```
