<?php
	/*
		copyright 2014 WWU Libraries
		license: MIT
		version 1.0
		author: David Bass
		summary: redirect short URL to Primo
		example:
			http://library.wwu.edu/onesearch/bellingham/wwu_only will be redirected to
			http://onesearch.library.wwu.edu/primo_library/libweb/action/dlSearch.do?institution=WWU&vid=WWU&query=any,contains,bellingham&search_scope=Books&src=permalink

		todo:
			- facets
			- sort-by
			- advanced search
			- make scopes configuration easier
	*/

	/* this script relies on an .htacess file 	*/

	$url_array = parse_url($_SERVER['REQUEST_URI']);
	$path = isset($url_array['path']) ? $url_array['path'] : '';
	$path_parts = explode("/", $path);
    $query = "";
    $scope = "&search_scope=All";

    $source = "&src=permalink";     // in case there is no referrer
    if(isset($_SERVER['HTTP_REFERER'])) {
		# the referrer can be really long; if so, lets just get the hostname;
		$referrer = $_SERVER['HTTP_REFERER'];
		$src = parse_url($referrer, PHP_URL_HOST);
        $src = str_replace('.', '_', $src);
		$source = "&src=" . urlencode($src);
    }

	/*
	echo "<pre>";
	var_dump($path_parts);
	echo "</pre>";
	*/

	if ($path_parts[2] != "") {
        # the search query in this example is "underground+newspaper+collection"
        # http://library.wwu.edu/onesearch/underground+newspaper+collection
		$query = $path_parts[2];
        $query = str_replace('%252F', '/', $query);     // replace underscores with slash, because slashes were replaced with underscores in javascript when permalink was generated (to bypass Apache rewriterule issue)

        // if this is a doi, we need to remove the "doi:" from the start of the query and surround the query with quotes for Primo to be able to find it;
        $doi = strrpos($query, "DOI:");
        if ($doi !== false) {
            $query_parts = explode("-author:", $query);
            $doi = $query_parts[0];
            $author = $query_parts[1];

            $query = str_replace('DOI:', '"', $doi) . '" ';     // remove the 'DOI:' and surround the doi with quotes and a trailing space
            $query .= str_replace('-author:', '', $author);     // remove '-author:' from the query
        }

	} else {
		echo "Search query was empty.";
		exit();
	}

	if ($path_parts[3] != "") {
        # the scope in the following example URL is "At WWU Only"
            # http://library.wwu.edu/onesearch/underground+newspaper+collection/wwu_only
        # here the scope is "WWU Libraries + Summit"
            # http://library.wwu.edu/onesearch/underground+newspaper+collection/wwu_summit

        $scope = $path_parts[3];
        if ($scope == "wwu_only") {
            $scope = "&search_scope=Books";
        } elseif ($scope == "wwu_summit") {
            $scope = "&search_scope=wwu_summit";
        } elseif ($scope == "worldcat") {
            $scope = "&search_scope=WorldCat";
        }
    }


 	$destination = "http://onesearch.library.wwu.edu/primo_library/libweb/action/dlSearch.do?institution=WWU&vid=WWU&query=any,contains," . $query . $scope . $source;

  	header ('HTTP/1.1 301 Moved Permanently');
  	header ('Location: ' . $destination );

	# TODO: log this in Google Analytics?

  	exit();
?>