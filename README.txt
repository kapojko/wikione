WikiOne
-------

The simplest wiki+tracking system written on PHP+MySql.

Author Kapoyko Yu. A., Russia <kapojko@yandex.ru>.

This work is published into the Public Domain.

INSTALLATION:

1. Unzip, checkout or export all to some local folder;
2. Upload content of 'upload' folder into desired folder on 'www', ex. 'www/tracker' (one can use 'upload.zip' archive to do that);
3. Copy 'config-dist.php' to 'config.php' and set all variables to approriate values;
4. Execute 'sql/install.sql' SQL program on your database (if you choose non-default table prefix in file 'config.php' you must fix the script manually);
5. DONE.

UPDATE TO NEW VERSION:

1. Unzip, checkout or export all to some local folder;
2. Upload content of 'upload' folder into your folder on 'www', ex. 'www/tracker' (one can use 'upload.zip' archive to do that);
3. If database structure must be updated, there will be a file named 'sql/convert_<PREV_VERSION>_to_<NEW_VERSION>.sql'; you must execute this SQL program on your database (if you choose non-default table prefix in file 'config.php' you must fix the script manually);
4. DONE.

UNINSTALLATION:

1. Execute 'sql/uninstall.sql' SQL program on your database (if you choose non-default table prefix in file 'config.php' you must fix the script manually);
2. Remove your folder on 'www', ex. 'www/tracker';
3. DONE.
