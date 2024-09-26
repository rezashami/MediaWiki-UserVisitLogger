# MediaWiki-UserVisitLogger

This repository contains a MediaWiki extension that shows the usernames that visit a specific page.
The code is written with PHP and tested on the MedaiWiki version 1.42.1.

## How to intall
First, navigate to the MediaWiki installed folder, then run these commands:

```bash
  cd extensions/
  git clone https://github.com/rezashami/MediaWiki-UserVisitLogger.git
```

## Load the extension
Add the name of the extension to the ```LocalSettings.php ``` file. 

To do this, please add the below code to the ```LocalSettings.php ``` :

```PHP
    wfLoadExtension('UserVisitLogger');
```

Subsequently, you need to run the update maintenance command for running SQL commands in the ```sql/UserVisitLogger.sql``` file. According to the official documents of the [MedaiWiki](https://www.mediawiki.org/wiki/Manual:Update.php), you should run the below command to execute the SQL commands automatically.

```bash
php maintenance/run.php update
```
## Config
There is a config for this extension. It controls the appearance of the total visit counter after the usernames. The default value is false. You can set it in the ```LocalSettings.php ``` file with the following command:
```PHP
  $wgUserVisitLogger['show_count'] = true;
```

## Usage
After installing and loading the extension, you can see the visitors of the specific page at the bottom.

## Uninstal the Extension
For uninstal the extenstion, just remove the UserVisitLogger folder in the ```extensions/``` folder. And then remove the added codes to the ```LocalSettings.php``` file.

Additionally, if you want to remove the created table in the MediaWiki's database, you must run this command in the SQL command line:

```SQL
DROP TABLE user_visit_log;
```
