[![Code Climate](https://codeclimate.com/github/sparkfabrik/sparktool/badges/gpa.svg)](https://codeclimate.com/github/sparkfabrik/sparktool)
|
[![Test Coverage](https://codeclimate.com/github/sparkfabrik/sparktool/badges/coverage.svg)](https://codeclimate.com/github/sparkfabrik/sparktool/coverage)
|
[![Travis Build](https://api.travis-ci.org/sparkfabrik/sparktool.svg?branch=develop)](https://travis-ci.org/sparkfabrik/sparktool)
|
[![Coverage Status](https://coveralls.io/repos/sparkfabrik/sparktool/badge.svg)](https://coveralls.io/r/sparkfabrik/sparktool)

# Table of Contents

* [Synopsis](#synopsis)
* [Team Members](#team-members)
* [Installation](#installation)
* [Usage](#usage)

# <a name="team-members"></a>Synopsis

[Explan the project]

# <a name="team-members"></a>Team Members
* "Paolo Mainardi" <paolo.mainardi@sparkfabrik.com>
* "Vincenzo Di Biaggio" <vincenzo.dibiaggio@sparkfabrik.com>

#<a name="installation"></a>Installation

Download the latest release:

```
wget -O /usr/local/bin/spark https://gitlab.agavee.com/paolo.mainardi/spark-tool/raw/release/spark.phar
```

Or just clone the project from: `gitlab@gitlab.agavee.com:paolo.mainardi/spark-tool.git`


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
