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
