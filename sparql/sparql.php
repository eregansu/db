<?php

/* Copyright 2013 Mo McRoberts.
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

uses('curl', 'uri');

require_once(dirname(__FILE__) . '/../db.php');

/* Generic SPARQL client interface */
class SPARQL implements IDatabase
{
    protected $endpoint;
    protected $updateEndpoint;
    protected $queryEndpoint;
    
    public function __construct($info)
    {
        if(substr($info['scheme'], -5) == '+http')
        {
            $info['scheme'] = 'http';
        }
        else if(substr($info['scheme'], -6) == '+https')
        {
            $info['scheme'] = 'https';
        }
        if(substr($info['path'], -1) != '/')
        {
            $info['path'] .= '/';
        }
        $this->endpoint = new URI($info);
        $e = new URI($this->endpoint);
        $e->path .= 'update/';
        $e->query = null;
        $e->fragment = null;
        $this->updateEndpoint = strval($e);;
        $e = new URI($this->endpoint);
        $e->path .= 'sparql/';
        $e->query = null;
        $e->fragment = null;
        $this->queryEndpoint = strval($e);
    }
    
    /* Execute a pre-formatted SPARQL query */
    protected function execute($sparql, $expectResult = false)
    {
        $c = new Curl($expectResult ? $this->queryEndpoint : $this->updateEndpoint);
        $c->httpVersion = CURL_HTTP_VERSION_1_0;
        $c->headers['Accept'] = 'application/json;q=1.0, application/sparql-results+json;q=1.0, */*;q=0.5';
        $c->headers['Expect'] = null;
        $c->headers['Connection'] = 'close';
        $c->headers['Transfer-Encoding'] = null;
        if($expectResult)
        {
            $payload = http_build_query(array('query' => $sparql));
        }
        else
        {
            $payload = http_build_query(array('update' => $sparql));        
        }
        $c->headers['Content-Length'] = strlen($payload) ;
        $c->postFields = $payload;
        $c->httpPOST = true;
        $c->returnTransfer = true;
        $result = array();
        $result['payload'] = $c->exec();
        $result['info'] = $c->info;
        if(!strcmp($result['info']['content_type'], 'application/sparql-results+json') || !strcmp($result['info']['content_type'], 'application/json'))
        {
            $result['payload'] = json_decode($result['payload'], true);
        }
        if(strcmp($result['info']['http_code'], '200'))
        {
            throw new DBException($result['info']['http_code'], $result['payload'], $sparql);
        }
        return $result;
    }
       
	/* Execute any (parametized) statement, expecting a boolean result */	
    public function exec($query)
    {
		$params = func_get_args();
		array_shift($params);
		$sparql = preg_replace('/(\?.?)/e', "\$this->_quote(\"\\1\", \$params)", $query);
		$result = $this->execute($sparql, false);
        if(isset($result['payload']['results']['boolean']))
        {
            return $result['payload']['results']['boolean'];
        }
        return true;
    }
    
	public function execArray($query, $params)
	{
		if(!is_array($params)) $params = array();
		$sparql = preg_replace('/(\?.?)/e', "\$this->_quote(\"\\1\", \$params)", $query);
		$result = $this->execute($sparql, false);
        if(isset($result['payload']['results']['boolean']))
        {
            return $result['payload']['results']['boolean'];
        }        
        return true;
	}

    /* Execute any (parametized) statement, expecting a resultset */
	public function query($query)
	{
		$params = func_get_args();
		array_shift($params);
		$sparql = preg_replace('/(\?.?)/e', "\$this->_quote(\"\\1\", \$params)", $query);
		$result = $this->execute($sparql, true);
        if(!is_array($result['payload']))
        {
            return null;
        }
        return new SPARQLResultSet($result['payload']);
	}
    
    /* Execute any (parametized) statement, expecting a resultset */
	public function queryArray($query, $params)
	{
		if(!is_array($params)) $params = array();
		$sparql = preg_replace('/(\?.?)/e', "\$this->_quote(\"\\1\", \$params)", $query);
		$result = $this->execute($sparql, true);
        if(!is_array($result['payload']))
        {
            return null;
        }
        return new SPARQLResultSet($result['payload']);
	}
        
    protected function _quote($str, &$params)
    {
        if(strlen($str) == 2 && ctype_alnum($str[1]))
        {
            return $str;
        }
        $param = array_shift($params);
        if($param instanceof URI)
        {
            return '<' . $param . '>';
        }
        return '"' . addslashes($param) . '"';
    }
}

class SPARQLResultSet implements IDataSet
{
	public $fields = array();
	public $EOF = true;
	public $total = 0;
    protected $columns;
	protected $results;
	protected $count = 0;
    protected $key = null;
    
	public function __construct($results)
	{
        $this->columns = isset($results['head']['vars']) ? $results['head']['vars'] : array();
        $this->results = isset($results['results']['bindings']) ? $results['results']['bindings'] : array();
        $this->count = count($this->results);
        $this->EOF = ($this->count ? false : true);
	}
    
    public function next()
    {
        if($this->EOF) return null;
        if(!$this->row())
        {
            $this->EOF = true;
            return false;
        }
        return $this->fields;
    }
    
    public function rewind()
    {
        $this->key = null;
        $this->EOF = ($this->count ? false : true);
        $this->fields = array();
    }
    
	public function current()
	{
        if($this->key === null)
        {
            $this->next();
        }
        return $this->fields;
    }
    
    public function key()
    {
        if($this->key === null)
        {
            $this->next();
        }
        return $this->key;
    }
    
	public function valid()
	{
		if($this->key === null)
		{
			$this->next();
		}
		return !$this->EOF;
	}
	
	public function count()
	{
		return $this->count;
	}
    
    protected function row()
    {
        $this->fields = array();
        if($this->key === null)
        {
            if(!$this->count)
            {
                return false;
            }
            $this->key = 0;
        }
        else
        {
            $this->key++;
        }
        if(!isset($this->results[$this->key]))
        {
            return false;
        }
        $row = array();
        foreach($this->results[$this->key] as $column => $value)
        {
            switch($value['type'])
            {
            case 'uri':
                $value['value'] = new URI($value['value']);
                break;
            }
            $row[$column] = $value;
        }
        $this->fields = $row;
        return true;
    }
}