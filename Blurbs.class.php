<?php
/*
   Copyright 2012 Matt Baer

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

     http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
*/
/**
 * Master blurb class
 * @author Matt Baer
 */
class Blurbs {
	var $blurbs;
	var $rawBlurbs;
	var $categories;
	var $datafile;
	var $count;
	var $limit;
	var $select;
	
	/**
	 * Constructs a collection of blurbs from the specified data file
	 *
	 * @param string $datafile Path to the text file containing the blurbs
	 * @return Blurbs
	 */
	function Blurbs($datafile, $limit = -1) {
		$this->datafile = $datafile;
		$this->count = 0;
		$this->limit = $limit;
		
		if (isset($_GET['cat']))
			$this->select = $_GET['cat'];
		else
			$this->select = '';
		
		$this->setBlurbs();
		//$this->setCategories();
	}
	
	/**
	 * Reads in the blurbs
	 * @access private
	 */
	function setBlurbs() {
		if (file_exists($this->datafile)) {
			$temp = file($this->datafile);
			$p = new Parser();
			$p->setSelect($this->select);
			
			if ($this->limit == -1)
				$this->limit = sizeof($temp);
			
			$c = 0;
			for ($i=0; $i<$this->limit; $i++) {
				$b = explode(' - ', $temp[$i]);
				$new = new Blurb($b[0], $b[1]);
				
				if ($this->select == '') {
					// Exclude the sticky notes
					if (!$p->blurbContainsTag(strtolower('sticky'), $b[1])) {
						$this->rawBlurbs[$c] = $new;
						$this->blurbs[$c] = $p->parse($new);
						$c++;
					}
				} else {
					if ($p->blurbContainsTag(strtolower($this->select), $b[1])) {
						$this->rawBlurbs[$c] = $new;
						$this->blurbs[$c] = $p->parse($new);
						$c++;
					}
				}
							
				//$this->count++;
			}
			$this->count = sizeof($this->blurbs);
		} else {
			$this->count = 0;
		}
	}
	
	/**
	 * Gets all blurb objects as a sorted array
	 *
	 * @param string $sort Sort string: either 'asc' or 'desc'
	 * @return array All blurb objects
	 */
	function getBlurbs($sort = 'asc') {
		if ($sort == 'desc' && $this->size() > 0)
			$this->blurbs = array_reverse($this->blurbs);
		return $this->blurbs;
	}
	
	/**
	 * Returns how many total blurbs have been posted
	 *
	 * @return int How many total blurbs have been posted
	 */
	function size() {
		return $this->count;
	}
	
	/**
	 * Returns all categories
	 *
	 * @return array All categories
	 */
	function getCategories() {
		return $this->categories;
	}
	
	/**
	 * Adds a new blurb to the collection
	 *
	 * @param Blurb $blurb The blurb to be added
	 */
	function add($blurb) {
		$p = new Parser();
		$new = new Blurb(time(), $blurb);
		$this->blurbs[$this->count] = $new; //$p->parse($new);
		$this->appendData($new);
		$this->count++;
	}
	
	/**
	 * Adds a new blurb to the data file, formatted for program use
	 * @access private
	 * @param Blurb $blurb The blurb to be added
	 */
	function appendData($blurb) {
		$fp = fopen($this->datafile, "a");
		if ($fp) {
			fwrite($fp, $blurb->getTimestamp() . ' - ' . $blurb->getContent() . "\r\n");
			fclose($fp);
		} else {
			echo 'Couldn\'t write';
		}
	}
	
	function getFile() {
		$temp = $this->rawBlurbs;
		$res = '';
		
		foreach($temp as $blurb) {
			$res .= $blurb->toString();
		}
		
		return $res;
	}
}

/**
 * Individual blurb class. Stores all blurb information.
 *
 */
class Blurb {
	var $timestamp;
	var $date;
	var $content;
	var $dateFmt = "M j, y g:i a";
	
	/**
	 * Constructs a blurb
	 *
	 * @param mixed $postdate Either an int UNIX timestamp or string date
	 * @param string $blurb Blurb content
	 * @return Blurb
	 */
	function Blurb($postdate, $blurb) {
		if (is_numeric($postdate))
			$this->timestamp = $postdate;
		else
			$this->date = $postdate;
		
		$this->content = stripslashes($blurb);
	}
	
	/**
	 * Returns the Unix timestamp of when the blurb was posted
	 *
	 * @return int Unix timestamp of when the blurb was posted
	 */
	function getTimestamp() {
		return $this->timestamp;
	}
	
	/**
	 * Returns a human-readable date for this blurb
	 *
	 * @return string Human-readable date for this blurb
	 */
	function getTime() {
		$res = '';
		
		if ($this->timestamp != null)
			$res .= date($this->dateFmt, $this->timestamp);
		else
			$res .= $this->date;
		
		return $res;
	}
	
	/**
	 * Returns the blurb text
	 *
	 * @return string Blurb content
	 */
	function getContent() {
		return trim($this->content);
	}
	
	/**
	 * Sets the blurb text
	 * @access private
	 */
	function setContent($inp) {
		$this->content = $inp;
	}
	
	/**
	 * Returns the blurb as a human-readable string
	 *
	 * @return string Blurb as a full string
	 */
	function toString() {
		$res = '';
		
		$res .= '<time>' . $this->getTime() . '</time>';
		$res .= ' - ' . $this->getContent();
		return $res;
	}
}

class Parser extends Blurbs {
	var $tagChar = '!';
	var $dirChar = '#';
	var $select = '';
	var $tmpCount;
	
	function Parser() { }
	
	/**
	 * Sets a select condition (such as a tag)
	 *
	 * @param string $select Condition for filtering results
	 */
	function setSelect($select) {
		$this->select = $select;
	}
	
	/**
	 * Returns whether or not the blurb contains a specific tag
	 *
	 * @param string $tag Tag to search for
	 * @param string $blurbcontent Blurb contents to be searched for the tag
	 * @return boolean True if the specified tag is found
	 */
	function blurbContainsTag($tag, $blurbcontent) {
		return preg_match('/'.$this->tagChar.$tag.'(\s{0,1})/i', $blurbcontent);
	}
	
	/**
	 * Performs all auto-magical functions on an individual blurb
	 *
	 * @param Blurb $blurb
	 * @return Blurb The updated blurb
	 */
	function parse($blurb) {
		$blurb = $this->format($this->replaceLinks($this->replaceTags($blurb)));
		
		return $blurb;
	}
	
	/**
	 * Finds and replaces all tags
	 * 
	 * @access private
	 * @param Blurb $b
	 * @return Blurb The updated, outputtable blurb
	 */
	function replaceTags($b) {
		if ($this->select != '') {
			$b->setContent(preg_replace('/'.$this->tagChar.'(\w+)(\s{0,1})/', "<a href=\"?cat=$1\">".$this->tagChar."$1</a>$2", $b->getContent()));
			$b->setContent(preg_replace('/'.$this->tagChar.$this->select.'(\s{0,1})/', "<strong>".$this->tagChar.$this->select."</strong>$1", $b->getContent()));
		} else {
			$b->setContent(preg_replace('/'.$this->tagChar.'(\w+)(\s{0,1})/', "<a href=\"?cat=$1\">".$this->tagChar."$1</a>$2", $b->getContent()));
		}
		
		return $b;
	}
	
	/**
	 * Finds and replaces all links in the specified blurb
	 * 
	 * @access private
	 * @param Blurb $b
	 * @return Blurb The updated, outputtable blurb
	 */
	function replaceLinks($b) {
		//$b->setContent(preg_replace('/(https?:\/\/.*((\.[a-z0-9]{2,3}\/[a-z0-9]*)|\/))/i', '<a href="$1">$1</a>', $b->getContent()));
		$b->setContent(preg_replace('/(http:\/\/.*\.[a-zA-Z]{2,3}\/{0,1}[^\s]{0,})(\s{0,1})/', "<a href=\"$1\">$1</a>$2", $b->getContent()));
		
		return $b;
	}
	
	function replaceDirectives($b) {
		// TODO: preg_match to find actual directive
		$b->setContent(preg_replace('/'.$this->dirChar.'(\w+)(\s{0,1})/', "<strong>".$this->dirChar."$1</strong>$2", $b->getContent()));
		
		return $b;
	}
	
	function format($b) {
		$b->setContent(preg_replace('/\*([a-zA-Z0-9\']+)\*/', "<strong>$1</strong>", $b->getContent()));
		$b->setContent(preg_replace('/_([a-zA-Z0-9\']+)_/', "<em>$1</em>", $b->getContent()));
		
		return $b;
	}
}
?>
