## Usage

### Search by date

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
