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
