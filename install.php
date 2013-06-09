<?php

/* Copyright 2012-2013 Mo McRoberts.
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */

class BuiltinDBModuleInstall extends BuiltinModuleInstaller
{
	public $coexists = true;

	public function writeAppConfig($file, $isSoleWebModule = false, $chosenSoleWebModule = null)
	{
		fwrite($file, "\$AUTOLOAD['database'] = PLATFORM_ROOT . 'db/db.php';\n");
		fwrite($file, "\$AUTOLOAD['dbschema'] = PLATFORM_ROOT . 'db/dbschema.php';\n");
		fwrite($file, "\$AUTOLOAD['searchengine'] = PLATFORM_ROOT . 'db/searchengine.php';\n");
        fwrite($file, "\$EREGANSU_MODULES['db'] = PLATFORM_ROOT . 'db/db.php';\n");
        fwrite($file, "\$EREGANSU_MODULES['searchengine'] = PLATFORM_ROOT . 'db/searchengine.php';\n");
		fwrite($file, "\$URI_SCHEMES['mysql']['Database'] = array('file' => PLATFORM_ROOT . 'db/sql/mysql.php', 'class' => 'MySQL');\n");
		fwrite($file, "\$URI_SCHEMES['mysql']['DBSchema'] = array('file' => PLATFORM_ROOT . 'db/sql/mysql-schema.php', 'class' => 'MySQLSchema');\n");
		fwrite($file, "\$URI_SCHEMES['sqlite3']['DBSchema'] = array('file' => PLATFORM_ROOT . 'db/sql/sqlite3-schema.php', 'class' => 'SQLite3Schema');\n");
		fwrite($file, "\$URI_SCHEMES['ldap']['Database'] = array('file' => PLATFORM_ROOT . 'db/directory/ldap.php', 'class' => 'LDAP');\n");
		fwrite($file, "\$URI_SCHEMES['sparql+http']['Database'] = array('file' => PLATFORM_ROOT . 'db/sparql/sparql.php', 'class' => 'SPARQL');\n");
		fwrite($file, "\$URI_SCHEMES['sparql+https']['Database'] = array('file' => PLATFORM_ROOT . 'db/sparql/sparql.php', 'class' => 'SPARQL');\n");
		fwrite($file, "\$URI_SCHEMES['http']['SearchEngine'] = array('file' => PLATFORM_ROOT . 'db/searchengine.php', 'class' => 'GenericWebSearch');\n");
		fwrite($file, "\$URI_SCHEMES['https']['SearchEngine'] = array('file' => PLATFORM_ROOT . 'db/searchengine.php', 'class' => 'GenericWebSearch');\n");
		fwrite($file, "\$URI_SCHEMES['dbplite']['SearchEngine'] = array('file' => PLATFORM_ROOT . 'db/search/dbpedialite.php', 'class' => 'DbpediaLiteSearch');\n");
		fwrite($file, "\$URI_SCHEMES['xapian+file']['SearchEngine'] = array('file' => PLATFORM_ROOT . 'db/search/xapian.php', 'class' => 'XapianSearch');\n");
		fwrite($file, "\$URI_SCHEMES['xapian+file']['SearchIndexer'] = array('file' => PLATFORM_ROOT . 'db/search/xapian.php', 'class' => 'XapianIndexer');\n");
	}
    
    public function writeInstanceConfig($file)
    {
        fwrite($file, "/* Define the below to log executed SPARQL statements */\n");
        fwrite($file, "/* define('EREGANSU_SPARQL_DEBUG_QUERIES', true); */\n\n");
    }
}
